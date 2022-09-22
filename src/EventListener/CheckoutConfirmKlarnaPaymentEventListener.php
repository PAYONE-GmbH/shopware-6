<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\KlarnaSessionService\KlarnaSessionServiceInterface;
use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutConfirmKlarnaPaymentEventListener implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    private $translator;
    /** @var KlarnaSessionServiceInterface */
    private $klarnaSessionService;

    public function __construct(
        TranslatorInterface $translator,
        KlarnaSessionServiceInterface $klarnaSessionService
    ) {
        $this->translator           = $translator;
        $this->klarnaSessionService = $klarnaSessionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class       => ['initiateSession', -20],
            AccountEditOrderPageLoadedEvent::class      => ['initiateSession', -20],
            HandlePaymentMethodRouteRequestEvent::class => 'onHandlePaymentMethodRouteRequest',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function initiateSession($event): void
    {
        $currentPaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();

        if (!$this->isKlarnaPaymentMethod($currentPaymentMethod)) {
            return;
        }

        $order = $event instanceof AccountEditOrderPageLoadedEvent ? $event->getPage()->getOrder() : null;

        try {
            $sessionStruct = $this->klarnaSessionService->createKlarnaSession(
                $event->getSalesChannelContext(),
                $order ? $order->getId() : null
            );

            $currentExtension = $event->getPage()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
            $currentExtension->assign([
                'klarnaSessionStruct'                       => $sessionStruct,
                CheckoutCartPaymentData::DATA_WORK_ORDER_ID => $sessionStruct->getWorkorderId(),
                CheckoutCartPaymentData::DATA_CART_HASH     => $sessionStruct->getCartHash(),
            ]);
        } catch (PayoneRequestException $e) {
            $session = $event->getRequest()->getSession();
            $session->getFlashBag()->add(
                'danger',
                $this->translator->trans('PayonePayment.errorMessages.canNotInitKlarna')
            );
        }
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
