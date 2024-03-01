<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class KlarnaPaymentMethodFilter extends DefaultPaymentFilterService
{
    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        if ($this->hasCustomProducts($filterContext)) {
            throw new PaymentMethodNotAllowedException('PAYONE does not support custom products within Klarna payments');
        }
    }

    private function hasCustomProducts(PaymentFilterContext $filterContext): bool
    {
        // PAYOSWXP-50: we also added this check cause the SwagCustomProducts extension does have a few issues, which
        // makes it very expensive to fix them in the Payone extension. So we exclude the payment method
        // if there are any "custom product" within the order/cart

        if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector')) {
            $order = $filterContext->getOrder();
            $cart = $filterContext->getCart();
            if ($order) {
                foreach ($order->getLineItems() ?? new OrderLineItemCollection() as $item) {
                    if ($item->getType() === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE) {
                        return true;
                    }
                }
            } elseif ($cart) {
                foreach ($cart->getLineItems() as $item) {
                    if ($item->getType() === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
