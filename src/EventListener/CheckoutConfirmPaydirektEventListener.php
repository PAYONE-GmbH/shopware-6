<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePaydirekt;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This event listener removes the Paydirekt payment method for customers
 * which have a billing address that is outside from DE.
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

    /**
     * @param AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hidePaydirektForNonDeCustomers($event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if (
            $this->isCurrency($event->getSalesChannelContext(), 'EUR') &&
            $this->isBillingAddressFromCountry($event->getSalesChannelContext(), 'DE')
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePaydirekt::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }
}
