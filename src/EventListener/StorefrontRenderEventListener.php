<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Service\ActivePaymentMethodsLoaderService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class StorefrontRenderEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ActivePaymentMethodsLoaderService $activePaymentMethodsLoader,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
        ];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        $event->setParameter(
            'activePayonePaymentMethodIds',
            $this->activePaymentMethodsLoader->getActivePaymentMethodIds($event->getSalesChannelContext()),
        );
    }
}
