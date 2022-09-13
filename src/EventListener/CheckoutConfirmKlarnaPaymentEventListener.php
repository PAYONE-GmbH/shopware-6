<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmKlarnaPaymentEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HandlePaymentMethodRouteRequestEvent::class => 'onHandlePaymentMethodRouteRequest',
        ];
    }

    public function onHandlePaymentMethodRouteRequest(HandlePaymentMethodRouteRequestEvent $event): void
    {
        if (!$this->isKlarnaPaymentMethod($event->getSalesChannelContext()->getPaymentMethod())) {
            return;
        }

        // when user is changing the payment method, no custom params will be sent to the payment handler.
        // so we need to transfer the parameters, which are required for the payment handler from the storefront-request to the api-request in the background.
        $paramsToTransfer = ['workorder', 'carthash', 'payoneKlarnaAuthorizationToken'];
        foreach ($paramsToTransfer as $key) {
            if ($event->getStorefrontRequest()->request->has($key)) {
                $event->getStoreApiRequest()->request->set($key, $event->getStorefrontRequest()->request->get($key));
            }
        }
    }

    private function isKlarnaPaymentMethod(PaymentMethodEntity $currentPaymentMethod): bool
    {
        return is_subclass_of($currentPaymentMethod->getHandlerIdentifier(), AbstractKlarnaPaymentHandler::class);
    }
}
