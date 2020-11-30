<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayoneEps;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This event listener removes the EPS payment method for customers
 * which have a billing address that is outside from AT.
 */
class CheckoutConfirmEpsEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideEpsForNonAtCustomers',
            AccountPaymentMethodPageLoadedEvent::class => 'hideEpsForNonAtCustomers',
            AccountEditOrderPageLoadedEvent::class     => 'hideEpsForNonAtCustomers',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hideEpsForNonAtCustomers($event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if (
            $this->isEuroCurrency($event->getSalesChannelContext()) &&
            $this->isAtCustomer($event->getSalesChannelContext())
        ) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayoneEps::UUID);
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
     * is inside AT or not. Or false if no customer or billing
     * address is given.
     */
    private function isAtCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress || null === $billingAddress->getCountry()) {
            return false;
        }

        return $billingAddress->getCountry()->getIso() === 'AT';
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
