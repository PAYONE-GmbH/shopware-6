<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmKlarnaRestrictPaymentEventListener implements EventSubscriberInterface
{
    private const ALLOWED_COUNTRIES = ['AT', 'DK', 'FI', 'DE', 'NL', 'NO', 'SE', 'CH'];
    private const ALLOWED_B2B_COUNTRIES = ['FI', 'DE', 'NO', 'SE'];
    private const ALLOWED_CURRENCIES = ['EUR', 'DKK', 'NOK', 'SEKCHF'];

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hidePaymentMethod',
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethod',
            AccountEditOrderPageLoadedEvent::class => 'hidePaymentMethod',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hidePaymentMethod(PageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        $billingAddress = $customer ? $customer->getActiveBillingAddress() : null;

        if ($this->isCurrencyAllowed($context->getCurrency()->getIsoCode())
            && (!$billingAddress || $this->isAddressAllowed($billingAddress))
            && !$this->hasCustomProducts($event)
        ) {
            return;
        }

        $event->getPage()->setPaymentMethods($this->removeKlarnaMethods($event->getPage()->getPaymentMethods()));
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $address
     */
    private function isAddressAllowed($address): bool
    {
        $country = $address->getCountry();

        return $country && \in_array($country->getIso(), self::ALLOWED_COUNTRIES, true)
            && (!$address->getCompany() || \in_array($country->getIso(), self::ALLOWED_B2B_COUNTRIES, true));
    }

    private function isCurrencyAllowed(string $currencyCode): bool
    {
        return \in_array($currencyCode, self::ALLOWED_CURRENCIES, true);
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

    private function removeKlarnaMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        return $paymentMethodCollection->filter(static function (PaymentMethodEntity $paymentMethod) {
            return !is_subclass_of($paymentMethod->getHandlerIdentifier(), AbstractKlarnaPaymentHandler::class);
        });
    }
}
