<?php

declare(strict_types=1);

namespace PayonePayment\Components\AmazonPay\EventListener;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\PaymentMethod\PayoneAmazonPayExpress;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\AmazonPayExpressUpdateCheckoutSessionStruct;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestParameterFactory $requestParameterFactory,
        private readonly PayoneClientInterface $payoneClient,
        private readonly CartExtensionService $cartExtensionService,
        private readonly CartHasher $cartHasher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 200],
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($event->getSalesChannelContext()->getPaymentMethod()->getId() !== PayoneAmazonPayExpress::UUID) {
            return;
        }

        $cart = $event->getPage()->getCart();

        $cartExtension = $this->cartExtensionService->getCartExtension($cart);
        if ($cartExtension === null) {
            return;
        }

        if ($this->cartHasher->validate($cart, $cartExtension->getCartHash(), $event->getSalesChannelContext())) {
            // we only want to call the update checkout-session if something has been changed within the cart.
            return;
        }

        $requestParameter = $this->requestParameterFactory->getRequestParameter(new AmazonPayExpressUpdateCheckoutSessionStruct(
            $event->getSalesChannelContext(),
            $cartExtension->getWorkorderId()
        ));

        try {
            $this->payoneClient->request($requestParameter);
            $this->cartExtensionService->addCartExtension($cart, $event->getSalesChannelContext(), $cartExtension->getWorkorderId()); // this will generate a new hash for the cart.
        } catch (PayoneRequestException) {
            // we ignore this error. maybe the user can still checkout. Error got already logged within the payone client.
        }
    }
}
