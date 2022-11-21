<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class KlarnaPaymentMethodFilter extends DefaultPaymentFilterService
{
    public function filterPaymentMethodsAdditionalCheck(PaymentMethodCollection $methodCollection, PageLoadedEvent $event): PaymentMethodCollection
    {
        if ($this->hasCustomProducts($event)) {
            $methodCollection = $this->removePaymentMethod($methodCollection);
        }

        return $methodCollection;
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function hasCustomProducts(PageLoadedEvent $event): bool
    {
        // PAYOSWXP-50: we also added this check cause the SwagCustomProducts extension does have a few issues, which
        // makes it very expensive to fix them in the Payone extension. So we exclude the payment method
        // if there are any "custom product" within the order/cart

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            foreach ($event->getPage()->getCart()->getLineItems() as $item) {
                if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector')
                    && $item->getType() === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE) {
                    return true;
                }
            }
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            foreach ($event->getPage()->getOrder()->getLineItems() ?? new OrderLineItemCollection() as $item) {
                if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector')
                    && $item->getType() === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE) {
                    return true;
                }
            }
        }

        return false;
    }
}
