<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Amazon\AbstractAmazonRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\AmazonPayExpressUpdateCheckoutSessionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

class UpdateCheckoutSessionParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        private readonly CurrencyPrecisionInterface $currencyPrecision,
        private readonly CartService $cartService
    ) {
    }

    /**
     * @param AmazonPayExpressUpdateCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cart = $this->cartService->getCart($arguments->getSalesChannelContext()->getToken(), $arguments->getSalesChannelContext());

        $currency = $arguments->getSalesChannelContext()->getCurrency();

        return [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'clearingtype' => AbstractAmazonRequestParameterBuilder::CLEARING_TYPE,
            'wallettype' => AbstractAmazonRequestParameterBuilder::WALLET_TYPE,
            'add_paydata[action]' => 'updateCheckoutSession',
            'amount' => $this->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
            'workorderid' => $arguments->getWorkorderId(),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof AmazonPayExpressUpdateCheckoutSessionStruct;
    }
}
