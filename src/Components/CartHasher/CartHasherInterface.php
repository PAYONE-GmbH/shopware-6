<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CartHasherInterface
{
    public function generate(Cart $cart, SalesChannelContext $context): string;

    public function validate(OrderEntity $orderEntity, string $cartHash, SalesChannelContext $context): bool;
}
