<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\EventListener;

use PayonePayment\Provider\GooglePay\ButtonConfiguration;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
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
            OffcanvasCartPageLoadedEvent::class => 'onCartLoaded',
            CheckoutCartPageLoadedEvent::class  => 'onCartLoaded',
        ];
    }

    public function onCartLoaded(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        $page->addExtension(
            'payoneGooglePayButton',
            $this->buttonConfiguration->getButtonConfiguration($event->getSalesChannelContext()),
        );
    }
}