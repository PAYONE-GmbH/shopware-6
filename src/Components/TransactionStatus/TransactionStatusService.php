<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionTypeEnum;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Service\CurrencyPrecisionService;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    final public const STATUS_PREFIX = 'paymentStatus';

    /**
     * States for which a "failed" notification is ignored instead of applying the configured mapping.
     */
    private const FAILED_NOTIFICATION_PROTECTED_STATES = [
        OrderTransactionStates::STATE_AUTHORIZED,
        OrderTransactionStates::STATE_PAID,
        OrderTransactionStates::STATE_PARTIALLY_PAID,
        OrderTransactionStates::STATE_REFUNDED,
        OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
        OrderTransactionStates::STATE_CHARGEBACK,
    ];

    public function __construct(
        private readonly StateMachineRegistry $stateMachineRegistry,
        private readonly ConfigReaderInterface $configReader,
        private readonly EntityRepository $transactionRepository,
        private readonly LoggerInterface $logger,
        private readonly CurrencyPrecisionService $currencyPrecision,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public function transitionByConfigMapping(
        SalesChannelContext $salesChannelContext,
        PaymentTransaction $paymentTransaction,
        array $transactionData,
    ): void {
        $configuration       = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $currency            = $paymentTransaction->getOrder()->getCurrency();
        $orderTransaction    = $paymentTransaction->getOrderTransaction();
        $paymentMethodEntity = $orderTransaction->getPaymentMethod();

        if (null === $currency) {
            return;
        }

        if ($this->isTransactionPartialPaid($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . \ucfirst(TransactionActionEnum::PARTIAL_CAPTURE->value);
        } elseif ($this->isTransactionPartialRefund($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . \ucfirst(TransactionActionEnum::PARTIAL_DEBIT->value);
        } else {
            $configurationKey = self::STATUS_PREFIX . \ucfirst(\strtolower((string) $transactionData['txaction']));
        }

        $transitionName = $configuration->getString($configurationKey);

        if (null !== $paymentMethodEntity) {
            $paymentMethod       = $this->paymentMethodRegistry->getByHandler($paymentMethodEntity->getHandlerIdentifier());
            $configurationPrefix = $paymentMethod::getConfigurationPrefix();

            /** @var string $transitionName */
            $transitionName = $configuration->getByPrefix(
                $configurationKey,
                $configurationPrefix,
                $configuration->getString($configurationKey),
            );
        }

        if (empty($transitionName)) {
            $paymentMethodIdentifier = (null !== $paymentMethodEntity)
                ? $paymentMethodEntity->getHandlerIdentifier()
                : 'unknown'
            ;

            $this->logger->info(
                'No status transition configured',
                [
                    'configurationKey' => $configurationKey,
                    'paymentMethod'    => $paymentMethodIdentifier,
                ],
            );

            return;
        }

        $this->executeTransition(
            $salesChannelContext->getContext(),
            $orderTransaction->getId(),
            \strtolower($transitionName),
            $transactionData,
        );
    }

    #[\Override]
    public function transitionByName(
        Context $context,
        string $transactionId,
        string $transitionName,
        array $parameter = [],
    ): void {
        $this->executeTransition($context, $transactionId, \strtolower($transitionName), $parameter);
    }

    private function executeTransition(
        Context $context,
        string $transactionId,
        string $transitionName,
        array $transactionData = [],
    ): void {
        $transaction = $this->fetchOrderTransactionWithState($context, $transactionId);

        if (null === $transaction || null === $machineStateEntity = $transaction->getStateMachineState()) {
            return;
        }

        if ($this->shouldIgnoreFailedNotification($transactionData, $machineStateEntity->getTechnicalName())) {
            return;
        }

        if (
            StateMachineTransitionActions::ACTION_PAID === $transitionName
            && OrderTransactionStates::STATE_PARTIALLY_PAID === $machineStateEntity->getTechnicalName()
        ) {
            // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition, see https://github.com/shopwareLabs/SwagPayPal/blob/b63efb9/src/Util/PaymentStatusUtil.php#L79
            $this->executeTransition(
                $context,
                $transactionId,
                StateMachineTransitionActions::ACTION_DO_PAY,
                $transactionData,
            );
        }

        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderTransactionDefinition::ENTITY_NAME,
                    $transactionId,
                    $transitionName,
                    'stateId',
                ),
                $context,
            );
        } catch (IllegalTransitionException) {
            /** false-positiv handling (paid -> paid, open -> open) */
            $this->logger->notice(\sprintf(
                'Transition %s not possible from state %s for transaction ID %s',
                $transitionName,
                $machineStateEntity->getTechnicalName(),
                $transactionId,
            ), $transactionData);
        }
    }

    private function isTransactionPartialPaid(array $transactionData, CurrencyEntity $currency): bool
    {
        if (
            !\array_key_exists('transactiontype', $transactionData)
            || !\array_key_exists('receivable', $transactionData)
            || !\array_key_exists('price', $transactionData)
            || !\array_key_exists('invoice_grossamount', $transactionData)
        ) {
            return false;
        }

        if (TransactionTypeEnum::GT->value === $transactionData['transactiontype']) {
            return false;
        }

        $validAction = \in_array(
            \strtolower((string) ($transactionData['txaction'] ?? '')),
            [
                TransactionActionEnum::DEBIT->value,
                TransactionActionEnum::CAPTURE->value,
                TransactionActionEnum::INVOICE->value,
            ],
            true,
        );

        if (!$validAction) {
            return false;
        }

        $transactionDataReceivable = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['receivable'],
            $currency,
        );

        if (0 === $transactionDataReceivable) {
            return false;
        }

        $transactionDataPrice = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['price'],
            $currency,
        );

        if ($transactionDataReceivable === $transactionDataPrice) {
            return false;
        }

        $transactionDataInvoiceGrossAmount = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['invoice_grossamount'],
            $currency,
        );

        if ($transactionDataInvoiceGrossAmount === $transactionDataPrice) {
            return false;
        }

        return true;
    }

    private function isTransactionPartialRefund(array $transactionData, CurrencyEntity $currency): bool
    {
        if (TransactionActionEnum::DEBIT->value !== \strtolower((string) $transactionData['txaction'])) {
            return false;
        }

        if (!\array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (
            \array_key_exists('transactiontype', $transactionData)
            && TransactionTypeEnum::GT->value !== $transactionData['transactiontype']
        ) {
            return false;
        }

        $transactionDataReceivable = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['receivable'],
            $currency,
        );

        if (0 === $transactionDataReceivable) {
            return false;
        }

        return true;
    }

    /**
     * A "failed" notification is only relevant while the transaction is still pending. Once the
     * transaction already reached one of the protected states (e.g. it was authorized or paid in
     * the meantime), a "failed" notification is treated as late or duplicated and is ignored
     * instead of applying the configured mapping.
     *
     * Only applies to actual PAYONE "failed" webhooks - if the given data has no `txaction` (e.g.
     * a manual transition via transitionByName()), nothing is blocked.
     */
    private function shouldIgnoreFailedNotification(array $transactionData, string $currentState): bool
    {
        if (!isset($transactionData['txaction'])) {
            return false;
        }

        if (TransactionActionEnum::FAILED->value !== \strtolower((string) $transactionData['txaction'])) {
            return false;
        }

        return \in_array($currentState, self::FAILED_NOTIFICATION_PROTECTED_STATES, true);
    }

    private function fetchOrderTransactionWithState(Context $context, string $transactionId): OrderTransactionEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([$transactionId]);
        $criteria->addAssociation('stateMachineState');

        /** @var OrderTransactionEntity|null */
        return $this->transactionRepository->search($criteria, $context)->first();
    }
}
