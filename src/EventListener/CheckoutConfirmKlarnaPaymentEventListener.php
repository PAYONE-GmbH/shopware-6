<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutConfirmKlarnaPaymentEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HandlePaymentMethodRouteRequestEvent::class => 'onHandlePaymentMethodRouteRequest',
        ];
    }

    private function isKlarnaPaymentMethod(PaymentMethodEntity $currentPaymentMethod): bool
    {
        return is_subclass_of($currentPaymentMethod->getHandlerIdentifier(), AbstractKlarnaPaymentHandler::class);
    }

    public function onHandlePaymentMethodRouteRequest(HandlePaymentMethodRouteRequestEvent $event)
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
}
