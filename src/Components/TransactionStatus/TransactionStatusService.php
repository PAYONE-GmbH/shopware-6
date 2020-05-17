<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
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
    public const ACTION_PARTIAL_CAPTURE = 'partial_capture';
    public const ACTION_COMPLETED       = 'completed';
    public const ACTION_DEBIT           = 'debit';
    public const ACTION_PARTIAL_DEBIT   = 'partial_debit';
    public const ACTION_CANCELATION     = 'cancelation';
    public const ACTION_FAILED          = 'failed';

    public const STATUS_PREFIX    = 'paymentStatus';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';

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
        $configuration    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $configurationKey = self::STATUS_PREFIX . ucfirst(strtolower($transactionData['txaction']));

        if ($this->isTransactionPartialPaid($transactionData)) {
            $configurationKey = self::ACTION_PARTIAL_CAPTURE;
        } elseif ($this->isTransactionPartialRefund($transactionData)) {
            $configurationKey = self::ACTION_PARTIAL_DEBIT;
        }

        $transitionName = $configuration->get($configurationKey);

        if (empty($transitionName)) {
            if ($this->isTransactionOpen($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_REOPEN;
            } elseif ($this->isTransactionPartialPaid($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_PAY_PARTIALLY;
            }elseif ($this->isTransactionPaid($transactionData)) {
               $transitionName = StateMachineTransitionActions::ACTION_PAY;
            } elseif ($this->isTransactionPartialRefund($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_REFUND_PARTIALLY;
            } elseif ($this->isTransactionRefund($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_REFUND;
            } elseif ($this->isTransactionCancelled($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_CANCEL;
            }
        }

        if (!empty($transitionName)) {
            $this->executeTransition($salesChannelContext->getContext(), $paymentTransaction->getOrderTransaction()->getId(), strtolower($transitionName));
        }
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

    private function isTransactionPaid(array $transactionData): bool
    {
        if (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE &&
            array_key_exists('receivable', $transactionData) &&
            $this->getReciveableInt((string)$transactionData['receivable']) === 0) {
            return true;
        }


        return in_array(
            strtolower($transactionData['txaction']),
            [
                self::ACTION_PAID,
                self::ACTION_COMPLETED,
                self::ACTION_DEBIT,
            ],
            true
        );
    }

    private function isTransactionPartialPaid(array $transactionData): bool
    {
        if (in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE]) &&
            array_key_exists('receivable', $transactionData) &&
            $this->getReciveableInt((string)$transactionData['receivable']) !== 0) {
            return true;
        }

        return false;
    }

    private function isTransactionPartialRefund(array $transactionData): bool
    {
        if (strtolower($transactionData['txaction']) === self::ACTION_DEBIT &&
            array_key_exists('receivable', $transactionData) &&
            $this->getReciveableInt((string)$transactionData['receivable']) !== 0) {
            return true;
        }

        return false;
    }

    private function isTransactionRefund(array $transactionData): bool
    {
        if (in_array(strtolower($transactionData['txaction']), [self::ACTION_DEBIT, self::ACTION_CAPTURE]) &&
            array_key_exists('receivable', $transactionData) &&
            $this->getReciveableInt((string)$transactionData['receivable']) !== 0) {
            return true;
        }

        return false;
    }

    private function isTransactionCancelled(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_CANCELATION
            || strtolower($transactionData['txaction']) === self::ACTION_FAILED;
    }

    protected function getReciveableInt(string $receivable): int
    {
        $reciveableStrChr = strrchr($receivable, '.');

        if($reciveableStrChr) {
            return (int) ((float)$receivable * (10 ** strlen(substr($reciveableStrChr, 1))));
        }

        return (int) $receivable;
    }
}
