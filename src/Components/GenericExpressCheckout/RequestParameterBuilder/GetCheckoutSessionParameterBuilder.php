<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout\RequestParameterBuilder;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

class GetCheckoutSessionParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly CartService $cartService
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param GetCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cart = $this->cartService->getCart($arguments->getSalesChannelContext()->getToken(), $arguments->getSalesChannelContext());
        $currency = $arguments->getSalesChannelContext()->getCurrency();

        return [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'workorderid' => $arguments->getWorkorderId(),
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof GetCheckoutSessionStruct
            && \in_array($arguments->getPaymentMethod(), PaymentHandlerGroups::GENERIC_EXPRESS, true); // not required, just to be safe
    }
}
