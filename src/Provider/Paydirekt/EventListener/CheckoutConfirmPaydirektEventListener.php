<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Paydirekt\EventListener;

use PayonePayment\EventListener\ChecksBillingAddressCountry;
use PayonePayment\EventListener\ChecksCurrency;
use PayonePayment\EventListener\RemovesPaymentMethod;
use PayonePayment\Provider\Paydirekt\PaymentMethod\StandardPaymentMethod;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This event listener removes the Paydirekt payment method for customers
 * which have a billing address that is outside from DE.
 *
 * @deprecated No longer supported
 */
class CheckoutConfirmPaydirektEventListener implements EventSubscriberInterface
{
    use ChecksCurrency;
    use ChecksBillingAddressCountry;
    use RemovesPaymentMethod;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hidePaydirektForNonDeCustomers',
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaydirektForNonDeCustomers',
        ];
    }

    public function hidePaydirektForNonDeCustomers(
        AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event,
    ): void {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if (
            $this->isCurrency($event->getSalesChannelContext(), 'EUR')
            && $this->isBillingAddressFromCountry($event->getSalesChannelContext(), 'DE')
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, StandardPaymentMethod::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }
}
