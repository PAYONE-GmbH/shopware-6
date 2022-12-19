<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentFilterContextFactory implements PaymentFilterContextFactoryInterface
{
    private OrderFetcherInterface $orderFetcher;

    public function __construct(OrderFetcherInterface $orderFetcher)
    {
        $this->orderFetcher = $orderFetcher;
    }

    public function createContextForOrder(OrderEntity $order, SalesChannelContext $salesChannelContext): PaymentFilterContext
    {
        return new PaymentFilterContext(
            $salesChannelContext,
            $this->orderFetcher->getOrderBillingAddress($order),
            $this->orderFetcher->getOrderShippingAddress($order),
            $order->getCurrency(),
            $order,
            null
        );
    }

    public function createContextForCart(Cart $cart, SalesChannelContext $salesChannelContext): PaymentFilterContext
    {
        $customer = $salesChannelContext->getCustomer();

        return new PaymentFilterContext(
            $salesChannelContext,
            $customer ? $customer->getActiveBillingAddress() : null,
            $customer ? $customer->getActiveShippingAddress() : null,
            $salesChannelContext->getCurrency(),
            null,
            $cart
        );
    }
}
