<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\Context;
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

    public const STATUS_PREFIX    = 'paymentStatus';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';

    public const TRANSACTION_TYPE_GT = 'GT';

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        ConfigReaderInterface $configReader
    ) {
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->configReader         = $configReader;
    }

    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, PaymentTransaction $paymentTransaction, array $transactionData): void
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $currency      = $paymentTransaction->getOrder()->getCurrency();

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
                $transitionName = StateMachineTransitionActions::ACTION_PAY_PARTIALLY;
            } elseif ($this->isTransactionPaid($transactionData, $currency)) {
                $transitionName = StateMachineTransitionActions::ACTION_PAY;
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
        }
    }

    private function isTransactionOpen(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_APPOINTED;
    }

    private function isTransactionPaid(array $transactionData, ?CurrencyEntity $currency): bool
    {
        if (in_array(strtolower($transactionData['txaction']), [self::ACTION_PAID, self::ACTION_COMPLETED], true)) {
            return true;
        }

        if (!in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE], true)) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (!$currency) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if (array_key_exists('price', $transactionData) &&
            (int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())), 0)) {
            return true;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) === 0) {
            return true;
        }

        return false;
    }

    private function isTransactionPartialPaid(array $transactionData, ?CurrencyEntity $currency): bool
    {
        if (!in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE], true)) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (!$currency) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] === TransactionStatusService::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) === 0) {
            return false;
        }

        if (array_key_exists('price', $transactionData) &&
            (int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) === (int) round(((float) $transactionData['price'] * (10 ** $currency->getDecimalPrecision())), 0)) {
            return false;
        }

        return true;
    }

    private function isTransactionRefund(array $transactionData, ?CurrencyEntity $currency): bool
    {
        if (strtolower($transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (!$currency) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) !== 0) {
            return false;
        }

        return true;
    }

    private function isTransactionPartialRefund(array $transactionData, ?CurrencyEntity $currency): bool
    {
        if (strtolower($transactionData['txaction']) !== self::ACTION_DEBIT) {
            return false;
        }

        if (!array_key_exists('receivable', $transactionData)) {
            return false;
        }

        if (!$currency) {
            return false;
        }

        if (array_key_exists('transactiontype', $transactionData) && $transactionData['transactiontype'] !== self::TRANSACTION_TYPE_GT) {
            return false;
        }

        if ((int) round(((float) $transactionData['receivable'] * (10 ** $currency->getDecimalPrecision())), 0) === 0) {
            return false;
        }

        return true;
    }

    private function isTransactionCancelled(array $transactionData): bool
    {
        return in_array(strtolower($transactionData['txaction']), [self::ACTION_CANCELATION, self::ACTION_FAILED], true);
    }
}
