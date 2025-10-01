<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionTypeEnum;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
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

    public function __construct(
        private readonly StateMachineRegistry $stateMachineRegistry,
        private readonly ConfigReaderInterface $configReader,
        private readonly EntityRepository $transactionRepository,
        private readonly LoggerInterface $logger,
        private readonly CurrencyPrecisionService $currencyPrecision,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    public function transitionByConfigMapping(
        SalesChannelContext $salesChannelContext,
        PaymentTransaction $paymentTransaction,
        array $transactionData,
    ): void {
        if ($this->isAsyncCancelled($paymentTransaction, $transactionData)) {
            return;
        }

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
        $transactionCriteria = (new Criteria([$transactionId]))->addAssociation('stateMachineState');

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->transactionRepository->search($transactionCriteria, $context)->first();

        if (null === $transaction || null === $machineStateEntity = $transaction->getStateMachineState()) {
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
        $validAction = \in_array(
            \strtolower((string) $transactionData['txaction']),
            [
                TransactionActionEnum::DEBIT->value,
                TransactionActionEnum::CAPTURE->value,
                TransactionActionEnum::INVOICE->value
            ],
            true,
        );

        if (!$validAction) {
            return false;
        }

        if (
            \array_key_exists('transactiontype', $transactionData)
            && TransactionTypeEnum::GT->value === $transactionData['transactiontype']
        ) {
            return false;
        }

        $transactionDataReceivable = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['receivable'],
            $currency,
        );

        if (\array_key_exists('receivable', $transactionData) && 0 === $transactionDataReceivable) {
            return false;
        }

        $transactionDataPrice = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['price'],
            $currency,
        );

        if (
            \array_key_exists('price', $transactionData) && \array_key_exists('receivable', $transactionData)
            && $transactionDataReceivable === $transactionDataPrice
        ) {
            return false;
        }

        $transactionDataInvoiceGrossAmount = $this->currencyPrecision->getRoundedTotalAmount(
            (float) $transactionData['invoice_grossamount'],
            $currency,
        );

        if (
            \array_key_exists('price', $transactionData) && \array_key_exists('invoice_grossamount', $transactionData)
            && $transactionDataInvoiceGrossAmount === $transactionDataPrice
        ) {
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

    private function isAsyncCancelled(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        $payoneTransactionData = $paymentTransaction->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        if (null === $payoneTransactionData || empty($payoneTransactionData->getTransactionData())) {
            return false;
        }

        $payoneTransactionDataHistory = $payoneTransactionData->getTransactionData();
        $firstTransaction             = $payoneTransactionDataHistory[\array_key_first($payoneTransactionDataHistory)];

        if ($this->isFailedRedirect($firstTransaction, $transactionData)) {
            return true;
        }

        return false;
    }

    private function isFailedRedirect(array $firstTransaction, array $transactionData): bool
    {
        return
            \array_key_exists('response', $firstTransaction)
            && \array_key_exists('status', $firstTransaction['response'])
            && $firstTransaction['response']['status'] === \strtoupper(TransactionActionEnum::REDIRECT->value)
            && TransactionActionEnum::FAILED->value === \strtolower((string) $transactionData['txaction'])
        ;
    }
}
