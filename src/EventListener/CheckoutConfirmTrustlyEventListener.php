<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayoneTrustly;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This event listener removes the Trustly payment method for customers
 * which have a billing address that is outside from allowed country list.
 */
class CheckoutConfirmTrustlyEventListener implements EventSubscriberInterface
{
    protected const ALLOWED_BANK_COUNTRIES = [
        'DE',
        'DK',
        'EE',
        'ES',
        'FI',
        'IT',
        'MT',
        'NL',
        'NO',
        'PL',
        'SE',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideTrustlyForDisallowedCountryCustomers',
            AccountPaymentMethodPageLoadedEvent::class => 'hideTrustlyForDisallowedCountryCustomers',
            AccountEditOrderPageLoadedEvent::class     => 'hideTrustlyForDisallowedCountryCustomers',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hideTrustlyForDisallowedCountryCustomers($event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if (
            $this->isEuroCurrency($event->getSalesChannelContext()) &&
            $this->isAllowedCountryCustomer($event->getSalesChannelContext())
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayoneTrustly::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    /**
     * Returns whether or not the currency is EUR.
     */
    private function isEuroCurrency(SalesChannelContext $context): bool
    {
        return $context->getCurrency()->getIsoCode() === 'EUR';
    }

    /**
     * Returns whether or not the customer's billing address
     * is inside allowed countries or not.
     */
    private function isAllowedCountryCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress || null === $billingAddress->getCountry()) {
            return false;
        }

        return in_array($billingAddress->getCountry()->getIso(), self::ALLOWED_BANK_COUNTRIES, true);
    }

    private function removePaymentMethod(PaymentMethodCollection $paymentMethods, string $paymentMethodId): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentMethodId) {
                return $paymentMethod->getId() !== $paymentMethodId;
            }
        );
    }
}
