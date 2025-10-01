<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\EventListener;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\AmazonPay\PaymentHandler\ExpressPaymentHandler;
use PayonePayment\Provider\AmazonPay\PaymentMethod\ExpressPaymentMethod;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\CartHasherService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    public function __construct(
        private PaymentRequestEnricher $paymentRequestEnricher,
        private RequestParameterEnricherChain $requestEnricherChain,
        private ExpressPaymentHandler $paymentHandler,
        private PayoneClientInterface $payoneClient,
        private CartExtensionService $cartExtensionService,
        private CartHasherService $cartHasher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [ 'onConfirmPageLoaded', 200 ],
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (ExpressPaymentMethod::UUID !== $salesChannelContext->getPaymentMethod()->getId()) {
            return;
        }

        $cart          = $event->getPage()->getCart();
        $cartExtension = $this->cartExtensionService->getCartExtension($cart);

        if (null === $cartExtension) {
            return;
        }

        if ($this->cartHasher->validate($cart, $cartExtension->getCartHash(), $salesChannelContext)) {
            // we only want to call the update checkout-session if something has been changed within the cart.
            return;
        }

        $updateRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                new RequestDataBag(),
                $salesChannelContext,
                $cart,
                $this->paymentHandler,
            ),

            $this->requestEnricherChain,
        );

        try {
            $this->payoneClient->request($updateRequest->all());
            $this->cartExtensionService->addCartExtension(
                $cart,
                $salesChannelContext,
                $cartExtension->getWorkorderId(),
            ); // this will generate a new hash for the cart.
        } catch (PayoneRequestException) {
            // we ignore this error. maybe the user can still checkout. Error got already logged within the payone client.
        }
    }
}
