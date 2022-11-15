<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPrzelewy24EventListener implements EventSubscriberInterface
{
    private const ALLOWED_COUNTRIES = ['PL'];
    private const ALLOWED_CURRENCIES = ['PLN'];

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
        $page = $event->getPage();

        $billingAddress = $customer ? $customer->getActiveBillingAddress() : null;

        if ($this->isCurrencyAllowed($context->getCurrency()->getIsoCode())
            && (!$billingAddress || $this->isAddressAllowed($billingAddress))
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), [PayonePrzelewy24PaymentHandler::class]);

        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $address
     */
    private function isAddressAllowed($address): bool
    {
        $country = $address->getCountry();

        return $country && \in_array($country->getIso(), self::ALLOWED_COUNTRIES, true);
    }

    private function isCurrencyAllowed(string $currencyCode): bool
    {
        return \in_array($currencyCode, self::ALLOWED_CURRENCIES, true);
    }

    private function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentHandler): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return !\in_array($paymentMethod->getHandlerIdentifier(), $paymentHandler, true);
            }
        );
    }
}
