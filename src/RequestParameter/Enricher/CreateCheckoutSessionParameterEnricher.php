<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodAwareInterface;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Storefront\Controller\GenericExpressController;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Component\Routing\RouterInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class CreateCheckoutSessionParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
        private RequestBuilderServiceAccessor $serviceAccessor,
        private RouterInterface $router,
        private RedirectHandler $redirectHandler,
        private CartService $cartService,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $salesChannelContext = $arguments->salesChannelContext;
        $cart                = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $currency            = $salesChannelContext->getCurrency();
        $paymentMethod       = $this->paymentMethodRegistry->getByHandler($arguments->paymentHandler::class);

        // Not a Payone Payment Method
        if (null === $paymentMethod) {
            return [];
        }

        return [
            'request'    => RequestActionEnum::GENERIC_PAYMENT->value,

            'amount'     => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
                $cart->getPrice()->getTotalPrice(),
                $currency,
            ),

            'currency'   => $currency->getIsoCode(),
            'successurl' => $this->getReturnUrl(GenericExpressController::STATE_SUCCESS, $paymentMethod),
            'errorurl'   => $this->getReturnUrl(GenericExpressController::STATE_ERROR, $paymentMethod),
            'backurl'    => $this->getReturnUrl(GenericExpressController::STATE_CANCEL, $paymentMethod),
        ];
    }

    private function getReturnUrl(string $state, PaymentMethodInterface $paymentMethod): string
    {
        if (!$paymentMethod instanceof ExpressCheckoutPaymentMethodAwareInterface) {
            throw new \RuntimeException(
                \sprintf("%s can not be used for generic-express-checkout", $paymentMethod::class),
            );
        }

        return $this->redirectHandler->encode(
            $this->router->generate('frontend.account.payone.express-checkout.generic.return', [
                'paymentMethodId' => $paymentMethod::getId(),
                'state'           => $state,
            ]),
        );
    }
}
