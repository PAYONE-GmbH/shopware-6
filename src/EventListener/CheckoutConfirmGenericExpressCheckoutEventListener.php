<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmGenericExpressCheckoutEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        if (\in_array($paymentMethod->getHandlerIdentifier(), PaymentHandlerGroups::GENERIC_EXPRESS, true) === false) {
            // payment handler is not a generic express-checkout
            return;
        }

        $extension = $event->getPage()->getExtension(CheckoutConfirmPaymentData::EXTENSION_NAME) ?? new CheckoutConfirmPaymentData();
        $extension->assign([
            'showExitExpressCheckoutLink' => true,
            'preventAddressEdit' => true,
        ]);
        $event->getPage()->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $extension);
    }
}
