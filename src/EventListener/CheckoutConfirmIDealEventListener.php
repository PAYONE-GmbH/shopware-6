<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayoneIDeal;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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
        ];
    }

    /**
     * @param AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hideIDealForNonNlCustomers($event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        if ($this->isNlCustomer($event->getSalesChannelContext())) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayoneIDeal::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    /**
     * Returns whether or not the customer's billing address
     * is inside NL or not. Or false if no customer or billing
     * address is given.
     *
     * @param SalesChannelContext $context
     * @return bool
     */
    private function isNlCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return false;
        }

        return $billingAddress->getCountry()->getIso() === 'NL';
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
