<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\AmazonPayExpressUpdateCheckoutSessionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

class UpdateCheckoutSessionParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly CartService $cartService
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param AmazonPayExpressUpdateCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cart = $this->cartService->getCart($arguments->getSalesChannelContext()->getToken(), $arguments->getSalesChannelContext());

        $currency = $arguments->getSalesChannelContext()->getCurrency();

        return array_merge(parent::getRequestParameter($arguments), [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'updateCheckoutSession',
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
            'workorderid' => $arguments->getWorkorderId(),
        ]);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof AmazonPayExpressUpdateCheckoutSessionStruct;
    }
}
