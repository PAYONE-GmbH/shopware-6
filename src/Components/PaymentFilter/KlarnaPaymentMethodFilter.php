<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class KlarnaPaymentMethodFilter extends DefaultPaymentFilterService
{
    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): PaymentMethodCollection
    {
        $methodCollection = parent::filterPaymentMethods($methodCollection, $filterContext);

        $supportedPaymentMethods = $this->getSupportedPaymentMethods($methodCollection);
        if ($supportedPaymentMethods->count() === 0) {
            return $methodCollection;
        }

        if ($this->hasCustomProducts($filterContext)) {
            $methodCollection = $this->removePaymentMethods($methodCollection, $supportedPaymentMethods);
        }

        return $methodCollection;
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
