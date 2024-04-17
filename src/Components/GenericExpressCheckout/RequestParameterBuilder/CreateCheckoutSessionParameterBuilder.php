<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout\RequestParameterBuilder;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Storefront\Controller\GenericExpressController;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Component\Routing\RouterInterface;

class CreateCheckoutSessionParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly RouterInterface $router,
        private readonly RedirectHandler $redirectHandler,
        private readonly CartService $cartService
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param CreateExpressCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cart = $this->cartService->getCart($arguments->getSalesChannelContext()->getToken(), $arguments->getSalesChannelContext());

        $currency = $arguments->getSalesChannelContext()->getCurrency();

        return [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
            'successurl' => $this->getReturnUrl(GenericExpressController::STATE_SUCCESS, $arguments->getPaymentMethod()),
            'errorurl' => $this->getReturnUrl(GenericExpressController::STATE_ERROR, $arguments->getPaymentMethod()),
            'backurl' => $this->getReturnUrl(GenericExpressController::STATE_CANCEL, $arguments->getPaymentMethod()),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof CreateExpressCheckoutSessionStruct
            && \in_array($arguments->getPaymentMethod(), PaymentHandlerGroups::GENERIC_EXPRESS, true); // not required, just to be safe
    }

    private function getReturnUrl(string $state, string $paymentMethodHandlerClass): string
    {
        $paymentMethodId = array_search($paymentMethodHandlerClass, PaymentHandlerGroups::GENERIC_EXPRESS, true);
        if (!$paymentMethodId) {
            throw new \RuntimeException("$paymentMethodHandlerClass can not be used for generic-express-checkout");
        }

        return $this->redirectHandler->encode(
            $this->router->generate('frontend.account.payone.express-checkout.generic.return', [
                'paymentMethodId' => $paymentMethodId,
                'state' => $state,
            ])
        );
    }
}
