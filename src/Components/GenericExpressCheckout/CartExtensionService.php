<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
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

    public function addCartExtensionForExpressCheckout(
        Cart $cart,
        SalesChannelContext $context,
        string $paymentMethodId,
        string $workOrderId
    ): void {
        $cartData = new CheckoutCartPaymentData();

        $cartData->assign(array_filter([
            CheckoutCartPaymentData::DATA_WORK_ORDER_ID => $workOrderId,
            CheckoutCartPaymentData::DATA_CART_HASH => $this->cartHasher->generate($cart, $context),
        ]));

        $this->removeExtensionData($context, $cart, true); // make sure there is no checkout-data of PAYONE
        $cart->addExtension($this->getExtensionNameForExpressCheckout($paymentMethodId), $cartData);

        $this->cartService->recalculate($cart, $context);
    }

    public function getCartExtension(Cart $cart): ?CheckoutCartPaymentData
    {
        $extension = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        return $extension instanceof CheckoutCartPaymentData ? $extension : null;
    }

    public function getCartExtensionForExpressCheckout(Cart $cart, string $paymentMethodId): ?CheckoutCartPaymentData
    {
        $extension = $cart->getExtension($this->getExtensionNameForExpressCheckout($paymentMethodId));

        return $extension instanceof CheckoutCartPaymentData ? $extension : null;
    }

    public function removeExtensionData(SalesChannelContext $context, ?Cart $cart = null, bool $skipRecalculate = false): void
    {
        if ($cart === null) {
            $cart = $this->cartService->getCart($context->getToken(), $context);
        }

        foreach ($cart->getExtensions() as $key => $extension) {
            if ($extension instanceof CheckoutCartPaymentData) {
                $cart->removeExtension($key);
            }
        }

        if (!$skipRecalculate) {
            $this->cartService->recalculate($cart, $context);
        }
    }

    private function getExtensionNameForExpressCheckout(string $paymentMethodId): string
    {
        return CheckoutCartPaymentData::EXTENSION_NAME . $paymentMethodId . '_express';
    }
}
