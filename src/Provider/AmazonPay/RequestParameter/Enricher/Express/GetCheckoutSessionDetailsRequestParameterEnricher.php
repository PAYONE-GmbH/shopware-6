<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\RequestParameter\Enricher\Express;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Provider\AmazonPay\Enum\AmazonPayMetaEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class GetCheckoutSessionDetailsRequestParameterEnricher implements RequestParameterEnricherInterface
{
    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        return [
            'clearingtype'             => PayoneClearingEnum::WALLET->value,
            'wallettype'               => AmazonPayMetaEnum::WALLET_TYPE->value,
            'add_paydata[platform_id]' => AmazonPayMetaEnum::PLATFORM_ID->value,
            'add_paydata[action]'      => 'getCheckoutSession',
        ];
    }
}
