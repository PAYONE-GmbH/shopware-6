<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class OrderFetcher implements OrderFetcherInterface
{
    /** @var EntityRepositoryInterface */
    private $orderRepository;

    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrderById(string $orderId, Context $context): ?OrderEntity
    {
        if (mb_strlen($orderId, '8bit') === 16) {
            $orderId = Uuid::fromBytesToHex($orderId);
        }

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('id', $orderId));

        return $this->orderRepository->search($criteria, $context)->first();
    }

    private function getOrderCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('addresses.salutation');
        $criteria->addAssociation('addresses.country');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.positions');
        $criteria->addAssociation('deliveries.positions.orderLineItem');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.salutation');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('currency');
        $criteria->addSorting(new FieldSorting('lineItems.createdAt'));

        return $criteria;
    }
}
