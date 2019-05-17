<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    /**
     * // TODO: Add further mapping values when required
     *
     * `payone_payment_status.action` -> `state_machine_state.technical_name`
     *
     * @var array
     */
    private const STATE_MAPPING = [
        'appointed' => 'completed',
    ];

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        EntityRepositoryInterface $stateRepository
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateRepository            = $stateRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persistTransactionStatus(SalesChannelContext $salesChannelContext, array $transactionData): void
    {
        $orderTransaction = $this->getOrderTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            (int) $transactionData['txid']
        );

        if (!$orderTransaction) {
            throw new RuntimeException(sprintf(
                'Could not find an order transaction by payone transaction id "%s"',
                $transactionData['txid']
            ));
        }

        // Update the transaction state by the action that was provided by Payone
        $state = $this->getStateByTechnicalName($transactionData['txaction']);

        if (!$state) {
            return;
        }

        $transactionData = array_map('utf8_encode', $transactionData);

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields                                               = $orderTransaction->getCustomFields() ?? [];
        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER]        = (int) $transactionData['sequencenumber'];
        $customFields[CustomFieldInstaller::TRANSACTION_STATE] = $transactionData['status'];
        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $transactionData;

        $data = [
            'id'           => $orderTransaction->getId(),
            'customFields' => $customFields,
            'stateId'      => $state->getId(),
        ];

        $this->orderTransactionRepository->update([$data], $salesChannelContext->getContext());
    }

    private function getOrderTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?OrderTransactionEntity
    {
        $field = 'order_transaction.customFields.' . CustomFieldInstaller::TRANSACTION_ID;

        $criteria = new Criteria();
        $filter   = new EqualsFilter($field, $payoneTransactionId);
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
