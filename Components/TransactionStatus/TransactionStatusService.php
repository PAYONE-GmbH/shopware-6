<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    /** @var EntityRepositoryInterface */
    private $payoneStatusRepository;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(EntityRepositoryInterface $payoneStatusRepository, EntityRepositoryInterface $orderTransactionRepository)
    {
        $this->payoneStatusRepository     = $payoneStatusRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
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
        
        $data = [
            'orderTransactionId' => $transaction->getId(),
            'sequenceNumber' => (int) $status->sequenceNumber,
            'action' => $status->txAction,
            'reference' => $status->reference,
            'clearingType' => $status->clearingType,
            'price' => (float) $status->price
        ];

        $this->payoneStatusRepository->create([$data], Context::createDefaultContext());
    }

    private function getOrderTransactionByPayoneTransactionId(int $payoneTransactionId): ?OrderTransactionEntity
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();

        $filter = new EqualsFilter('order_transaction.payoneTransactionId', $payoneTransactionId);
        $criteria->addFilter($filter);

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}
