<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

use Shopware\Core\Checkout\Cart\Cart;

trait CartTrait
{
    /** @var Cart */
    protected $cart;

    public function getCart(): Cart
    {
        return $this->cart;
    }
}
