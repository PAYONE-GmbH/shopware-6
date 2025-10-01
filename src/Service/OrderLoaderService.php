<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;

readonly class OrderLoaderService
{
    public function __construct(
        private EntityRepository $orderRepository,
        private EntityRepository $currencyRepository,
    ) {
    }

    /**
     * @template T of bool
     *
     * @param T $throwException
     *
     * @return OrderEntity|null
     */
    public function getOrderById(string $orderId, Context $context, bool $throwException = false): OrderEntity|null
    {
        if (16 === \mb_strlen($orderId, '8bit')) {
            $orderId = Uuid::fromBytesToHex($orderId);
        }

        $criteria = ($this->getOrderCriteria())
            ->addFilter(new EqualsFilter('id', $orderId))
        ;

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        if (null === $order && $throwException) {
            throw new \RuntimeException('missing order');
        }

        return $order;
    }

    public function getOrderBillingAddress(OrderEntity $order): OrderAddressEntity
    {
        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            throw new \RuntimeException('missing order addresses');
        }

        /** @var OrderAddressEntity|null $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());

        if (null === $billingAddress) {
            throw new \RuntimeException('missing order billing address');
        }

        return $billingAddress;
    }

    public function getOrderShippingAddress(OrderEntity $order): OrderAddressEntity
    {
        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            throw new \RuntimeException('missing order addresses');
        }

        $deliveries = $order->getDeliveries();

        if ($deliveries && $deliveries->first()) {
            $shippingAddressId = $deliveries->first()->getShippingOrderAddressId();

            /** @var OrderAddressEntity|null $shippingAddress */
            $shippingAddress = $orderAddresses->get($shippingAddressId);

            if ($shippingAddress) {
                return $shippingAddress;
            }
        }

        return $this->getOrderBillingAddress($order);
    }

    public function getOrderCurrency(OrderEntity|null $order, Context $context): CurrencyEntity
    {
        if (null !== ($currency = $order?->getCurrency())) {
            return $currency;
        }

        $currencyId = null === $order
            ? $context->getCurrencyId()
            : $order->getCurrencyId()
        ;

        /** @var CurrencyEntity|null $currency */
        $currency = $this->currencyRepository->search(
            new Criteria([ $currencyId ]),
            $context,
        )->first();

        if (null === $currency) {
            throw new \RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    private function getOrderCriteria(): Criteria
    {
        return (new Criteria())
            ->addAssociations([
                'addresses',
                'addresses.salutation',
                'addresses.country',
                'billingAddress',
                'billingAddress.country',
                'currency',
                'deliveries',
                'deliveries.positions',
                'deliveries.positions.orderLineItem',
                'deliveries.shippingMethod',
                'deliveries.shippingOrderAddress',
                'deliveries.shippingOrderAddress.country',
                'deliveries.shippingOrderAddress.salutation',
                'deliveries.shippingOrderAddress.country',
                'lineItems',
                'orderCustomer.customer',
                'transactions',
                'transactions.stateMachineState',
            ])
            ->addSorting(new FieldSorting('lineItems.createdAt'))
        ;
    }
}
