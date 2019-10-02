<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePaypalExpress;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPaypalExpressEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hideInternalPaymentMethods',
        ];
    }

    public function hideInternalPaymentMethods(CheckoutConfirmPageLoadedEvent $event)
    {
        $internalPaymentMethods = [
            PayonePaypalExpress::UUID,
        ];

        $context = $event->getSalesChannelContext();

        $event->getPage()->setPaymentMethods(
            $event->getPage()->getPaymentMethods()->filter(
                static function (PaymentMethodEntity $entity) use ($internalPaymentMethods, $context) {
                    if ($context->getPaymentMethod()->getId() === $entity->getId()) {
                        return true;
                    }

                    return !in_array($entity->getId(), $internalPaymentMethods, true);
                }
            )
        );
    }
}
