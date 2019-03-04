<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    /**
     * `payone_payment_status.action` -> `state_machine_state.technical_name`
     *
     * @var array
     */
    private const STATE_MAPPING = [
        'appointed' => 'completed',
        //TODO: Add further mapping values when required
    ];

    /** @var EntityRepositoryInterface */
    private $payoneStatusRepository;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    public function __construct(EntityRepositoryInterface $payoneStatusRepository, EntityRepositoryInterface $orderTransactionRepository, EntityRepositoryInterface $stateRepository)
    {
        $this->payoneStatusRepository     = $payoneStatusRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateRepository            = $stateRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persistTransactionStatus(TransactionStatusStruct $status): void
    {
        $transaction = $this->getOrderTransactionByPayoneTransactionId((int) $status->txId);

        if (!$transaction) {
            throw new RuntimeException(sprintf('Could not find an order transaction by payone transaction id "%s"', $status->txId));
        }

        $context = Context::createDefaultContext();

        $data = [
            'orderTransactionId' => $transaction->getId(),
            'sequenceNumber'     => (int) $status->sequenceNumber,
            'action'             => $status->txAction,
            'reference'          => $status->reference,
            'clearingType'       => $status->clearingType,
            'price'              => (float) $status->price,
        ];

        $this->payoneStatusRepository->create([$data], $context);

        //Update the transaction state by the action that was provided by Payone
        $state = $this->getStateByTechnicalName($status->txAction);

        if (!$state) {
            return;
        }

        $data = [
            'id'      => $transaction->getId(),
            'stateId' => $state->getId(),
        ];

        $this->orderTransactionRepository->update([$data], $context);
    }

    private function getOrderTransactionByPayoneTransactionId(int $payoneTransactionId): ?OrderTransactionEntity
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $filter   = new EqualsFilter('order_transaction.payoneTransactionId', $payoneTransactionId);
        $criteria->addFilter($filter);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    private function getStateByTechnicalName(string $action): ?StateMachineStateEntity
    {
        if (!array_key_exists($action, self::STATE_MAPPING)) {
            return null;
        }

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $filter   = new EqualsFilter('state_machine_state.technicalName', self::STATE_MAPPING[$action]);
        $criteria->addFilter($filter);

        return $this->stateRepository->search($criteria, $context)->first();
    }
}
