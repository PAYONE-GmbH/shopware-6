<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

readonly class OrderTransactionLoaderService
{
    public function __construct(
        private EntityRepository $orderTransactionRepository,
    ) {
    }

    public function getOrderTransactionWithOrder(string $transactionId, Context $context): OrderTransactionEntity|null
    {
        $criteria = new Criteria([ $transactionId ]);

        $criteria->addAssociation('order');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.language');
        $criteria->addAssociation('order.billingAddress');
        $criteria->addAssociation('order.deliveries');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation(PayonePaymentOrderTransactionExtension::NAME);

        return $this->orderTransactionRepository
            ->search($criteria, $context)
            ->first()
        ;
    }
}
