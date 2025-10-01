<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\RequestParameter\Enricher\Express;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Provider\AmazonPay\Enum\AmazonPayMetaEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class UpdateCheckoutSessionParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private RequestBuilderServiceAccessor $serviceAccessor,
        private CartExtensionService $cartExtensionService,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $cart          = $arguments->cart;
        $cartExtension = $this->cartExtensionService->getCartExtension($cart);

        if (null === $cartExtension) {
            return [];
        }

        $currency = $arguments->salesChannelContext->getCurrency();
        $amount   = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $cart->getPrice()->getTotalPrice(), $currency,
        );

        return [
            'clearingtype'             => PayoneClearingEnum::WALLET->value,
            'wallettype'               => AmazonPayMetaEnum::WALLET_TYPE->value,
            'add_paydata[platform_id]' => AmazonPayMetaEnum::PLATFORM_ID->value,
            'request'                  => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'      => 'updateCheckoutSession',
            'amount'                   => $amount,
            'currency'                 => $currency->getIsoCode(),
            'workorderid'              => $cartExtension->getWorkorderId(),
        ];
    }
}
