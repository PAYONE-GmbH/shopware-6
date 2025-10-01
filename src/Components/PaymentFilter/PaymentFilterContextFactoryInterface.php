<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface PaymentFilterContextFactoryInterface
{
    public function createContextForOrder(
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
    ): PaymentFilterContext;

    public function createContextForCart(Cart $cart, SalesChannelContext $salesChannelContext): PaymentFilterContext;
}
