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

    private PayoneClientInterface $client;
    private RequestParameterFactory $requestFactory;
    private CartHasherInterface $cartHasher;
    private CartService $cartService;
    private SessionInterface $session;
    private TranslatorInterface $translator;

    public function __construct(
        PayoneClientInterface $client,
        RequestParameterFactory $requestFactory,
        CartHasherInterface $cartHasher,
        CartService $cartService,
        SessionInterface $session,
        TranslatorInterface $translator
    )
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->cartHasher = $cartHasher;
        $this->cartService = $cartService;
        $this->session = $session;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['initiateSession', -20],
            AccountEditOrderPageLoadedEvent::class => ['initiateSession', -20],
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
        $struct = new KlarnaCreateSessionStruct($event->getSalesChannelContext(), $order);
        $requestParams = $this->requestFactory->getRequestParameter($struct);

        try {
            $salesChannelContext = $event->getSalesChannelContext();

            $response = $this->client->request($requestParams);
            $payoneExtension = $event->getPage()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
            $payoneExtension->assign([
                'workOrderId' => $response['workorderid'],
                'klarnaClientToken' => $response['addpaydata']['client_token'],
                'klarnaPaymentMethodCategory' => $response['addpaydata']['payment_method_category_identifier'],
                'cartHash' => $this->cartHasher->generate(
                    $order ?? $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext),
                    $salesChannelContext
                )
            ]);
        } catch (PayoneRequestException $e) {
            $this->session->getFlashBag()->add(
                'danger',
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
            $this->session->getFlashBag()->add(
                'danger',
                $this->translator->trans('PayonePayment.errorMessages.canNotInitKlarna')
            );
        }
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
