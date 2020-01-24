<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    public const ACTION_APPOINTED        = 'appointed';
    public const ACTION_PAID             = 'paid';
    public const ACTION_CAPTURE          = 'capture';
    public const ACTION_COMPLETED        = 'completed';
    public const ACTION_DEBIT            = 'debit';
    public const ACTION_CANCELATION      = 'cancelation';
    public const ACTION_FAILED           = 'failed';

    public const STATUS_PREFIX    = 'paymentStatus';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var EntityRepositoryInterface */
    private $stateMachineTransitionRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        EntityRepositoryInterface $stateMachineTransitionRepository,
        ConfigReaderInterface $configReader,
        LoggerInterface $logger
    ) {
        $this->stateMachineRegistry             = $stateMachineRegistry;
        $this->stateMachineTransitionRepository = $stateMachineTransitionRepository;
        $this->configReader                     = $configReader;
        $this->logger                           = $logger;
    }

    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, OrderTransactionEntity $orderTransactionEntity, array $transactionData): void
    {
        $configuration    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $configurationKey = self::STATUS_PREFIX . ucfirst(strtolower($transactionData['txaction']));

        if ($this->isZeroCapture($transactionData)) {
            $configurationKey = self::STATUS_PREFIX . ucfirst(strtolower(self::ACTION_CANCELATION));
        }

        $transitionName = $configuration->get($configurationKey);

        if (empty($transitionName)) {
            if ($this->isTransactionOpen($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_REOPEN;
            } elseif ($this->isTransactionPaid($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_PAY;
            } elseif ($this->isTransactionCancelled($transactionData)) {
                $transitionName = StateMachineTransitionActions::ACTION_CANCEL;
            }
        }

        if (!empty($transitionName)) {
            $this->executeTransition($salesChannelContext->getContext(), $orderTransactionEntity->getId(), strtolower($transitionName));
        }
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
        if (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE && (float) $transactionData['receivable'] !== 0.0) {
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

    private function isTransactionCancelled(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_CANCELATION
            || strtolower($transactionData['txaction']) === self::ACTION_FAILED
            || $this->isZeroCapture($transactionData);
    }

    /**
     * This is a special case of a capture of 0, which means a cancellation
     */
    private function isZeroCapture(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_CAPTURE
            && (float) $transactionData['receivable'] === 0.0;
    }
}
