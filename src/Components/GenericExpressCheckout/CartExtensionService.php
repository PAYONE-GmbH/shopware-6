<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartExtensionService
{
    public function __construct(
        private readonly CartHasher $cartHasher,
        private readonly CartService $cartService,
    ) {
    }

    public function addCartExtension(
        Cart $cart,
        SalesChannelContext $context,
        string $workOrderId
    ): void {
        $cartData = new CheckoutCartPaymentData();

        $cartData->assign(array_filter([
            CheckoutCartPaymentData::DATA_WORK_ORDER_ID => $workOrderId,
            CheckoutCartPaymentData::DATA_CART_HASH => $this->cartHasher->generate($cart, $context),
        ]));

        $cart->addExtension(CheckoutCartPaymentData::EXTENSION_NAME, $cartData);

        $this->cartService->recalculate($cart, $context);
    }

    public function getCartExtension(Cart $cart): ?CheckoutCartPaymentData
    {
        $extension = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        return $extension instanceof CheckoutCartPaymentData ? $extension : null;
    }
}
