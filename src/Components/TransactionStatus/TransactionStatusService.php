<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    public const ACTION_APPOINTED       = 'appointed';
    public const ACTION_PAID            = 'paid';
    public const ACTION_CAPTURE         = 'capture';
    public const ACTION_PARTIAL_CAPTURE = 'partialCapture';
    public const ACTION_COMPLETED       = 'completed';
    public const ACTION_DEBIT           = 'debit';
    public const ACTION_PARTIAL_DEBIT   = 'partialDebit';
    public const ACTION_CANCELATION     = 'cancelation';
    public const ACTION_FAILED          = 'failed';
    public const ACTION_REDIRECT        = 'redirect';
    public const ACTION_INVOICE         = 'invoice';
    public const ACTION_UNDERPAID       = 'underpaid';
    public const ACTION_TRANSFER        = 'transfer';
    public const ACTION_REMINDER        = 'reminder';
    public const ACTION_VAUTHORIZATION  = 'vauthorization';
    public const ACTION_VSETTLEMENT     = 'vsettlement';

    public const STATUS_PREFIX    = 'paymentStatus';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_AUTHORIZATION    = 'authorization';
    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';

    public const TRANSACTION_TYPE_GT = 'GT';

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $transactionRepository,
        LoggerInterface $logger,
        CurrencyPrecisionInterface $currencyPrecision
    ) {
        $this->stateMachineRegistry  = $stateMachineRegistry;
        $this->configReader          = $configReader;
        $this->transactionRepository = $transactionRepository;
        $this->logger                = $logger;
        $this->currencyPrecision     = $currencyPrecision;
    }

    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, PaymentTransaction $paymentTransaction, array $transactionData): void
    {
        if ($this->isAsyncCancelled($paymentTransaction, $transactionData)) {
            return;
        }

        $configuration    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $currency         = $paymentTransaction->getOrder()->getCurrency();
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $paymentMethod    = $orderTransaction->getPaymentMethod();

        if (null === $currency) {
            return;
        }

        if ($this->isTransactionPartialPaid($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . ucfirst(self::ACTION_PARTIAL_CAPTURE);
        } elseif ($this->isTransactionPartialRefund($transactionData, $currency)) {
            $configurationKey = self::STATUS_PREFIX . ucfirst(self::ACTION_PARTIAL_DEBIT);
        } else {
            $configurationKey = self::STATUS_PREFIX . ucfirst(strtolower($transactionData['txaction']));
        }

        $transitionName = $configuration->getString($configurationKey);

        if (null !== $paymentMethod) {
            $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod->getHandlerIdentifier()];
            /** @var string $transitionName */
            $transitionName = $configuration->getByPrefix($configurationKey, $configurationPrefix, $configuration->getString($configurationKey));
        }

        if (empty($transitionName)) {
            $this->logger->info('No status transition configured',
                [
                    'configurationKey' => $configurationKey,
                    'paymentMethod'    => (null !== $paymentMethod) ? $paymentMethod->getHandlerIdentifier() : 'unknown',
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
        /** @var null|OrderTransactionEntity $transaction */
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
        } catch (IllegalTransitionException $exception) {
            /** false-positiv handling (paid -> paid, open -> open) */
            $this->logger->notice(sprintf('Transition %s not possible from state %s for transaction ID %s', $transitionName, $transaction->getStateMachineState()->getTechnicalName(), $transactionId), $transactionData);
        }
    }

    private function isTransactionPartialPaid(array $transactionData, CurrencyEntity $currency): bool
    {
        if (!in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE, self::ACTION_INVOICE], true)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === TransactionStatusService::TRANSACTION_TYPE_GT) {
            return false;
        }

        $precision = $this->currencyPrecision->getTotalRoundingPrecision($currency);

        if (array_key_exists('receivable', $transactionData) && (int) round(((float) $transactionData['receivable'] * (10 ** $precision))) === 0) {
            return false;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('receivable', $transactionData) &&
            (int) round(((float) $transactionData['receivable'] * (10 ** $precision))) === (int) round(((float) $transactionData['price'] * (10 ** $precision)))) {
            return false;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('invoice_grossamount', $transactionData) &&
            (int) round(((float) $transactionData['invoice_grossamount'] * (10 ** $precision))) === (int) round(((float) $transactionData['price'] * (10 ** $precision)))) {
            return false;
        }

        return true;
    }

    private function isTransactionPartialRefund(array $transactionData, CurrencyEntity $currency): bool
    {
        $precision = $this->currencyPrecision->getTotalRoundingPrecision($currency);

        if (strtolower($transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $precision))) === 0) {
            return false;
        }

        return true;
    }

    private function isAsyncCancelled(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $customFields = $paymentTransaction->getCustomFields();

        if (!array_key_exists(CustomFieldInstaller::TRANSACTION_DATA, $customFields)) {
            return false;
        }

        $fullTransactionData = $customFields[CustomFieldInstaller::TRANSACTION_DATA];
        $firstTransaction    = $fullTransactionData[array_key_first($fullTransactionData)];

        if ($this->isFailedRedirect($firstTransaction, $transactionData)) {
            return true;
        }

        return false;
    }

    private function isFailedRedirect(array $firstTransaction, array $transactionData): bool
    {
        return
            array_key_exists('response', $firstTransaction) &&
            array_key_exists('status', $firstTransaction['response']) &&
            $firstTransaction['response']['status'] === strtoupper(self::ACTION_REDIRECT) &&
            strtolower($transactionData['txaction']) === self::ACTION_FAILED;
    }
}
