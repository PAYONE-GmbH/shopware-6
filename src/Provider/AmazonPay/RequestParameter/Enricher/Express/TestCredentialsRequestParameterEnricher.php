<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\RequestParameter\Enricher\Express;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\AmazonPay\Enum\AmazonPayMetaEnum;
use PayonePayment\Provider\AmazonPay\PaymentHandler\ExpressPaymentHandler;
use PayonePayment\RequestParameter\Enricher\TestCredentialsRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\TestRequestParameterEnricherInterface;

readonly class TestCredentialsRequestParameterEnricher implements TestRequestParameterEnricherInterface
{
    use TestCredentialsRequestParameterEnricherTrait;

    #[\Override]
    public function isActive(): bool
    {
        return true;
    }

    #[\Override]
    public function getTestCredentials(): array
    {
        return [
            'request'             => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]' => 'createCheckoutSessionPayload',
            'clearingtype'        => PayoneClearingEnum::WALLET->value,
            'wallettype'          => AmazonPayMetaEnum::WALLET_TYPE->value,
            'amount'              => 100,
            'currency'            => 'EUR',
            'successurl'          => 'https://www.payone.com',
            'errorurl'            => 'https://www.payone.com',
            'backurl'             => 'https://www.payone.com',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return ExpressPaymentHandler::class;
    }
}
