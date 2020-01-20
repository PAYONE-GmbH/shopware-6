<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\DefinitionNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Exception\StateMachineInvalidEntityIdException;
use Shopware\Core\System\StateMachine\Exception\StateMachineInvalidStateFieldException;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    public const ACTION_APPOINTED        = 'appointed';
    public const ACTION_PAID             = 'paid';
    public const ACTION_CAPTURE          = 'capture';
    public const ACTION_COMPLETED        = 'completed';
    public const ACTION_DEBIT            = 'debit';
    public const ACTION_AUTHORIZATION    = 'authorization';
    public const ACTION_PREAUTHORIZATION = 'preauthorization';
    public const ACTION_CANCELATION      = 'cancelation';
    public const ACTION_FAILED           = 'failed';

    public const STATUS_PREFIX    = 'paymentStatus';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';
    public const AUTHORIZATION_TYPE_AUTHORIZATION    = 'authorization';

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

        if ((float) $transactionData['receivable'] === 0.0
            && strtolower($transactionData['txaction']) === self::ACTION_CAPTURE) {
            // This is a special case of a capture of 0, which means a cancellation
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
            $this->transitionByName($salesChannelContext->getContext(), $orderTransactionEntity, $transitionName);
        }
    }

    public function transitionByName(Context $context, OrderTransactionEntity $orderTransactionEntity, string $transitionName): void
    {
        $transitionName = strtolower($transitionName);

        if (!$this->isTransitionAllowed($context, $orderTransactionEntity->getStateId(), $transitionName)) {
            $this->logger->warning(sprintf(
                'Transaction %s is not allowed for state with id: %s',
                $transitionName,
                $orderTransactionEntity->getStateId()
            ));

            return;
        }

        $this->executeTransition($context, $orderTransactionEntity->getId(), $transitionName);
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
        } catch (DefinitionNotFoundException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        } catch (InconsistentCriteriaIdsException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        } catch (IllegalTransitionException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        } catch (StateMachineInvalidEntityIdException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        } catch (StateMachineInvalidStateFieldException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        } catch (StateMachineNotFoundException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        }
    }

    private function isTransitionAllowed(Context $context, string $currentStateId, string $transitionName): bool
    {
        if (empty($transitionName)) {
            return false;
        }

        $isAllowedCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('fromStateId', $currentStateId))
            ->addFilter(new EqualsFilter('actionName', $transitionName));

        $isAllowedSearchResult = $this->stateMachineTransitionRepository->search($isAllowedCriteria, $context);

        return $isAllowedSearchResult->getTotal() > 0;
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

        return in_array(strtolower($transactionData['txaction']), [
            self::ACTION_PAID,
            self::ACTION_COMPLETED,
            self::ACTION_DEBIT,
        ]);
    }

    private function isTransactionCancelled(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_CANCELATION
            || strtolower($transactionData['txaction']) === self::ACTION_FAILED
            || (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE
                && (float) $transactionData['receivable'] === 0.0);
    }
}
