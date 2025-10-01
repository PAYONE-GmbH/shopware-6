<?php

declare(strict_types=1);

namespace PayonePayment\Provider\IDeal\EventListener;

use PayonePayment\Provider\IDeal\PaymentMethod\StandardPaymentMethod;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This event listener removes the iDeal payment method for customers
 * which have a billing address that is outside from NL.
 */
class CheckoutConfirmIDealEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideIDealForNonNlCustomers',
            AccountPaymentMethodPageLoadedEvent::class => 'hideIDealForNonNlCustomers',
            AccountEditOrderPageLoadedEvent::class     => 'hideIDealForNonNlCustomers',
        ];
    }

    public function hideIDealForNonNlCustomers(
        AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event,
    ): void {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if (
            $this->isEuroCurrency($event->getSalesChannelContext())
            && $this->isNlCustomer($event->getSalesChannelContext())
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, StandardPaymentMethod::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    /**
     * Returns whether or not the currency is EUR.
     */
    private function isEuroCurrency(SalesChannelContext $context): bool
    {
        return 'EUR' === $context->getCurrency()->getIsoCode();
    }

    /**
     * Returns whether or not the customer's billing address
     * is inside NL or not. Or false if no customer or billing
     * address is given.
     */
    private function isNlCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress || null === $billingAddress->getCountry()) {
            return false;
        }

        return 'NL' === $billingAddress->getCountry()->getIso();
    }

    private function removePaymentMethod(PaymentMethodCollection $paymentMethods, string $paymentMethodId): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => $paymentMethod->getId() !== $paymentMethodId,
        );
    }
}
