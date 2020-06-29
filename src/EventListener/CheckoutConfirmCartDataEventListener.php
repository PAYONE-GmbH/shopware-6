<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmCartDataEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addCartData',
        ];
    }

    public function addCartData(CheckoutConfirmPageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if ($page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $event->getPage()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        /** @var null|CheckoutCartPaymentData $extension */
        $extension = $event->getPage()->getCart()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if (null !== $extension && null !== $payoneData) {
            $payoneData->assign([
                'workOrderId' => $extension->getWorkorderId(),
                'cartHash'    => $extension->getCartHash(),
            ]);
        }

        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }
}
