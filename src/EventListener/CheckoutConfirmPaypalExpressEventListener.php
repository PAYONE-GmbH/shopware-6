<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePaypalExpress;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPaypalExpressEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideInternalPaymentMethods',
            AccountPaymentMethodPageLoadedEvent::class => 'hideInternalPaymentMethods',
            AccountEditOrderPageLoadedEvent::class     => 'hideInternalPaymentMethods',
        ];
    }

    /** @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event */
    public function hideInternalPaymentMethods(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if ($event instanceof AccountEditOrderPageLoadedEvent) {
            return;
        }

        $activePaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();

        $page->setPaymentMethods(
            $this->filterPaymentMethods(
                $page->getPaymentMethods(),
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
