<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
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

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $transactionRepository,
        LoggerInterface $logger
    ) {
        $this->stateMachineRegistry  = $stateMachineRegistry;
        $this->configReader          = $configReader;
        $this->transactionRepository = $transactionRepository;
        $this->logger                = $logger;
    }

    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, PaymentTransaction $paymentTransaction, array $transactionData): void
    {
        if ($this->isAsyncCancelled($paymentTransaction, $transactionData)) {
            return;
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $currency      = $paymentTransaction->getOrder()->getCurrency();

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

        $transitionName = $configuration->get($configurationKey);

        if (empty($transitionName)) {
            if ($this->isTransactionOpen($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_REOPEN;
            } elseif ($this->isTransactionPartialPaid($transactionData, $currency)) {
                $transitionName = StateMachineTransitionActions::ACTION_PAID_PARTIALLY;
            } elseif ($this->isTransactionPaid($transactionData, $currency)) {
                $transitionName = StateMachineTransitionActions::ACTION_PAID;
            } elseif ($this->isTransactionPartialRefund($transactionData, $currency)) {
                $transitionName = StateMachineTransitionActions::ACTION_REFUND_PARTIALLY;
            } elseif ($this->isTransactionRefund($transactionData, $currency)) {
                $transitionName = StateMachineTransitionActions::ACTION_REFUND;
            } elseif ($this->isTransactionCancelled($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_CANCEL;
            }
        }

        if (empty($transitionName)) {
            return;
        }

        $this->executeTransition($salesChannelContext->getContext(), $paymentTransaction->getOrderTransaction()->getId(), strtolower($transitionName));
    }

    public function transitionByName(Context $context, string $transactionId, string $transitionName): void
    {
        $this->executeTransition($context, $transactionId, strtolower($transitionName));
    }

    private function executeTransition(Context $context, string $transactionId, string $transitionName): void
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
            $this->executeTransition($context, $transactionId, StateMachineTransitionActions::ACTION_DO_PAY);
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
            $this->logger->notice(sprintf('Transition %s not possible from state %s for transaction ID %s', $transitionName, $transaction->getStateMachineState()->getTechnicalName(), $transactionId));
        }
    }

    private function isTransactionOpen(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_APPOINTED;
    }

    private function isTransactionPaid(array $transactionData, CurrencyEntity $currency): bool
    {
        if (in_array(strtolower($transactionData['txaction']), [self::ACTION_PAID, self::ACTION_COMPLETED], true)) {
            return true;
        }

        if (!in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE, self::ACTION_INVOICE], true)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('receivable', $transactionData) &&
            (int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())))) {
            return true;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('invoice_grossamount', $transactionData) &&
            (int) round(((float) $transactionData['invoice_grossamount'] * (10 ** $currency->getDecimalPrecision()))) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())))) {
            return true;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) === 0) {
            return true;
        }

        return false;
    }

    private function isTransactionPartialPaid(array $transactionData, CurrencyEntity $currency): bool
    {
        if (!in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE, self::ACTION_INVOICE], true)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === TransactionStatusService::TRANSACTION_TYPE_GT) {
            return false;
        }

        if (array_key_exists('receivable', $transactionData) && (int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) === 0) {
            return false;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('receivable', $transactionData) &&
            (int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())))) {
            return false;
        }

        if (array_key_exists('price', $transactionData) && array_key_exists('invoice_grossamount', $transactionData) &&
            (int) round(((float) $transactionData['invoice_grossamount'] * (10 ** $currency->getDecimalPrecision()))) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())))) {
            return false;
        }

        return true;
    }

    private function isTransactionRefund(array $transactionData, CurrencyEntity $currency): bool
    {
        if (strtolower($transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) !== 0) {
            return false;
        }

        return true;
    }

    private function isTransactionPartialRefund(array $transactionData, CurrencyEntity $currency): bool
    {
        if (strtolower($transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision()))) === 0) {
            return false;
        }

        return true;
    }

    private function isTransactionCancelled(array $transactionData): bool
    {
        return in_array(strtolower($transactionData['txaction']), [self::ACTION_CANCELATION, self::ACTION_FAILED], true);
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

    private function isFailedRedirect(array $firstTransaction, array $transactionData)
    {
        return
            array_key_exists('response', $firstTransaction) &&
            array_key_exists('status', $firstTransaction['response']) &&
            $firstTransaction['response']['status'] === strtoupper(self::ACTION_REDIRECT) &&
            strtolower($transactionData['txaction']) === self::ACTION_FAILED;
    }
}
