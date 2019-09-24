<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartHasher
{
    public static function generateHash(Cart $cart, SalesChannelContext $context)
    {
        return hash('sha3-256', json_encode([$cart, $cart], JSON_PRESERVE_ZERO_FRACTION));
    }
}
