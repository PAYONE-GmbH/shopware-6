<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPayolutionEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hideInstallmentPaymentMethodForComapanies',
        ];
    }

    public function hideInstallmentPaymentMethodForComapanies(CheckoutConfirmPageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (null === $customer) {
            return;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return;
        }

        $event->getPage()->setPaymentMethods(
            $this->filterPaymentMethods(
                $event->getPage()->getPaymentMethods(),
                $billingAddress
            )
        );
    }

    private function filterPaymentMethods(PaymentMethodCollection $paymentMethods, CustomerAddressEntity $billingAddress): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($billingAddress) {
               if ($paymentMethod->getId() !== PayonePayolutionInstallment::UUID) {
                   return true;
               }

               return empty($billingAddress->getCompany());
            }
        );
    }
}
