<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePaypalExpress;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPaypalExpressEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideInternalPaymentMethods',
            AccountPaymentMethodPageLoadedEvent::class => 'hideInternalPaymentMethods',
        ];
    }

    /** @param AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event */
    public function hideInternalPaymentMethods($event): void
    {
        $activePaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();

        $event->getPage()->setPaymentMethods(
            $this->filterPaymentMethods(
                $event->getPage()->getPaymentMethods(),
                $activePaymentMethod
            )
        );
    }

    private function filterPaymentMethods(PaymentMethodCollection $paymentMethods, PaymentMethodEntity $activePaymentMethod): PaymentMethodCollection
    {
        $internalPaymentMethods = [
            PayonePaypalExpress::UUID,
        ];

        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($internalPaymentMethods, $activePaymentMethod) {
                if ($activePaymentMethod->getId() === $paymentMethod->getId()) {
                    return true;
                }

                return !in_array($paymentMethod->getId(), $internalPaymentMethods, true);
            }
        );
    }
}
