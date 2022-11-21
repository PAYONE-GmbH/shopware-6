<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\IterablePaymentFilter;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmRestrictPaymentEventListener implements EventSubscriberInterface
{
    private IterablePaymentFilter $iterablePaymentFilter;

    public function __construct(IterablePaymentFilter $iterablePaymentFilter)
    {
        $this->iterablePaymentFilter = $iterablePaymentFilter;
    }

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

        $paymentMethods = $event->getPage()->getPaymentMethods();
        $currency = $context->getCurrency()->getIsoCode();
        $billingAddress = $customer ? $customer->getActiveBillingAddress() : null;
        $shippingAddress = $customer ? $customer->getActiveShippingAddress() : null;

        $paymentMethods = $this->iterablePaymentFilter->filterPaymentMethods($paymentMethods, $currency, $billingAddress, $shippingAddress);
        $paymentMethods = $this->iterablePaymentFilter->filterPaymentMethodsAdditionalCheck($paymentMethods, $event);

        $event->getPage()->setPaymentMethods($paymentMethods);
    }
}
