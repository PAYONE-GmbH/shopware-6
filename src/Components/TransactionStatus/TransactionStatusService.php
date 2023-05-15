<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
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
    final public const ACTION_APPOINTED = 'appointed';
    final public const ACTION_PAID = 'paid';
    final public const ACTION_CAPTURE = 'capture';
    final public const ACTION_PARTIAL_CAPTURE = 'partialCapture';
    final public const ACTION_COMPLETED = 'completed';
    final public const ACTION_DEBIT = 'debit';
    final public const ACTION_PARTIAL_DEBIT = 'partialDebit';
    final public const ACTION_CANCELATION = 'cancelation';
    final public const ACTION_FAILED = 'failed';
    final public const ACTION_REDIRECT = 'redirect';
    final public const ACTION_INVOICE = 'invoice';
    final public const ACTION_UNDERPAID = 'underpaid';
    final public const ACTION_TRANSFER = 'transfer';
    final public const ACTION_REMINDER = 'reminder';
    final public const ACTION_VAUTHORIZATION = 'vauthorization';
    final public const ACTION_VSETTLEMENT = 'vsettlement';

    final public const STATUS_PREFIX = 'paymentStatus';
    final public const STATUS_COMPLETED = 'completed';

    final public const AUTHORIZATION_TYPE_AUTHORIZATION = 'authorization';
    final public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';

    final public const TRANSACTION_TYPE_GT = 'GT';

    public function __construct(
        private readonly StateMachineRegistry $stateMachineRegistry,
        private readonly ConfigReaderInterface $configReader,
        private readonly EntityRepository $transactionRepository,
        private readonly LoggerInterface $logger,
        private readonly CurrencyPrecisionInterface $currencyPrecision
    ) {
    }

    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, PaymentTransaction $paymentTransaction, array $transactionData): void
    {
        if ($this->isAsyncCancelled($paymentTransaction, $transactionData)) {
            return;
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $currency = $paymentTransaction->getOrder()->getCurrency();
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $paymentMethod = $orderTransaction->getPaymentMethod();

        if ($currency === null) {
            return;
        }

        if ($this->isTransactionPartialPaid($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . ucfirst(self::ACTION_PARTIAL_CAPTURE);
        } elseif ($this->isTransactionPartialRefund($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . ucfirst(self::ACTION_PARTIAL_DEBIT);
        } else {
            $configurationKey = self::STATUS_PREFIX . ucfirst(strtolower((string) $transactionData['txaction']));
        }

        $transitionName = $configuration->getString($configurationKey);

        if ($paymentMethod !== null) {
            $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod->getHandlerIdentifier()];
            /** @var string $transitionName */
            $transitionName = $configuration->getByPrefix($configurationKey, $configurationPrefix, $configuration->getString($configurationKey));
        }

        if (empty($transitionName)) {
            $this->logger->info(
                'No status transition configured',
                [
                    'configurationKey' => $configurationKey,
                    'paymentMethod' => ($paymentMethod !== null) ? $paymentMethod->getHandlerIdentifier() : 'unknown',
                ]
            );

            return;
        }

        $this->executeTransition($salesChannelContext->getContext(), $orderTransaction->getId(), strtolower($transitionName), $transactionData);
    }

    public function transitionByName(Context $context, string $transactionId, string $transitionName, array $parameter = []): void
    {
        $this->executeTransition($context, $transactionId, strtolower($transitionName), $parameter);
    }

    private function executeTransition(Context $context, string $transactionId, string $transitionName, array $transactionData = []): void
    {
        $transactionCriteria = (new Criteria([$transactionId]))
            ->addAssociation('stateMachineState');
        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->transactionRepository->search($transactionCriteria, $context)->first();

        if ($transaction === null || $transaction->getStateMachineState() === null) {
            return;
        }

        if ($transitionName === StateMachineTransitionActions::ACTION_PAID && $transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_PARTIALLY_PAID) {
            // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition, see https://github.com/shopwareLabs/SwagPayPal/blob/b63efb9/src/Util/PaymentStatusUtil.php#L79
            $this->executeTransition($context, $transactionId, StateMachineTransitionActions::ACTION_DO_PAY, $transactionData);
        }

        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderTransactionDefinition::ENTITY_NAME,
                    $transactionId,
                    $transitionName,
                    'stateId'
                ),
                $context
            );
        } catch (IllegalTransitionException) {
            /** false-positiv handling (paid -> paid, open -> open) */
            $this->logger->notice(sprintf('Transition %s not possible from state %s for transaction ID %s', $transitionName, $transaction->getStateMachineState()->getTechnicalName(), $transactionId), $transactionData);
        }
    }

    private function isTransactionPartialPaid(array $transactionData, CurrencyEntity $currency): bool
    {
        if (!\in_array(strtolower((string) $transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE, self::ACTION_INVOICE], true)) {
            return false;
        }

        if (\array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === TransactionStatusService::TRANSACTION_TYPE_GT) {
            return false;
        }

        $precision = $this->currencyPrecision->getTotalRoundingPrecision($currency);

        if (\array_key_exists('receivable', $transactionData) && (int) round((float) $transactionData['receivable'] * (10 ** $precision)) === 0) {
            return false;
        }

        if (\array_key_exists('price', $transactionData) && \array_key_exists('receivable', $transactionData)
            && (int) round((float) $transactionData['receivable'] * (10 ** $precision)) === (int) round((float) $transactionData['price'] * (10 ** $precision))) {
            return false;
        }

        if (\array_key_exists('price', $transactionData) && \array_key_exists('invoice_grossamount', $transactionData)
            && (int) round((float) $transactionData['invoice_grossamount'] * (10 ** $precision)) === (int) round((float) $transactionData['price'] * (10 ** $precision))) {
            return false;
        }

        return true;
    }

    private function isTransactionPartialRefund(array $transactionData, CurrencyEntity $currency): bool
    {
        $precision = $this->currencyPrecision->getTotalRoundingPrecision($currency);

        if (strtolower((string) $transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!\array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (\array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round((float) $transactionData['receivable'] * (10 ** $precision)) === 0) {
            return false;
        }

        return true;
    }

    private function isAsyncCancelled(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        $payoneTransactionData = $paymentTransaction->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if ($payoneTransactionData === null || empty($payoneTransactionData->getTransactionData())) {
            return false;
        }

        $payoneTransactionDataHistory = $payoneTransactionData->getTransactionData();
        $firstTransaction = $payoneTransactionDataHistory[array_key_first($payoneTransactionDataHistory)];

        if ($this->isFailedRedirect($firstTransaction, $transactionData)) {
            return true;
        }

        return false;
    }

    private function isFailedRedirect(array $firstTransaction, array $transactionData): bool
    {
        return \array_key_exists('response', $firstTransaction)
            && \array_key_exists('status', $firstTransaction['response'])
            && $firstTransaction['response']['status'] === strtoupper(self::ACTION_REDIRECT)
            && strtolower((string) $transactionData['txaction']) === self::ACTION_FAILED;
    }
}
