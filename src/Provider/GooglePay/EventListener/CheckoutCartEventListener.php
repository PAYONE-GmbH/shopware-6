<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\EventListener;

use PayonePayment\Provider\GooglePay\ButtonConfiguration;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutCartEventListener implements EventSubscriberInterface
{
    public function __construct(

        private ButtonConfiguration $buttonConfiguration,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'onCartLoaded',
        ];
    }

    public function onCartLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $page = $event->getPage();

        $page->addExtension(
            'payoneGooglePayButton',
            $this->buttonConfiguration->getButtonConfiguration($event->getSalesChannelContext(), $page->getCart()),
        );
    }
}
