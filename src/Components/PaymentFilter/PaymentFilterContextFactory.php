<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Service\AddressCompareService;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class PaymentFilterContextFactory implements PaymentFilterContextFactoryInterface
{
    public function __construct(
        private AddressCompareService $addressCompareService,
        private OrderLoaderService $orderLoaderService,
    ) {
    }

    public function createContextForOrder(
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
    ): PaymentFilterContext {
        return new PaymentFilterContext(
            $salesChannelContext,
            $this->addressCompareService,
            $this->orderLoaderService->getOrderBillingAddress($order),
            $this->orderLoaderService->getOrderShippingAddress($order),
            $order->getCurrency(),
            $order,
            null,
        );
    }

    public function createContextForCart(Cart $cart, SalesChannelContext $salesChannelContext): PaymentFilterContext
    {
        $customer = $salesChannelContext->getCustomer();

        return new PaymentFilterContext(
            $salesChannelContext,
            $this->addressCompareService,
            $customer?->getActiveBillingAddress(),
            $customer?->getActiveShippingAddress(),
            $salesChannelContext->getCurrency(),
            null,
            $cart,
        );
    }
}
