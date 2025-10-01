<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Paydirekt\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\Paydirekt\PaymentHandler\StandardPaymentHandler;
use PayonePayment\RequestParameter\Enricher\TestCredentialsRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\TestRequestParameterEnricherInterface;

readonly class TestCredentialsRequestParameterEnricher implements TestRequestParameterEnricherInterface
{
    use TestCredentialsRequestParameterEnricherTrait;

    public function isActive(): bool
    {
        return true;
    }

    public function getTestCredentials(): array
    {
        return [
            'request'                             => 'genericpayment',
            'clearingtype'                        => 'wlt',
            'wallettype'                          => 'PDT',
            'amount'                              => 10000,
            'currency'                            => 'EUR',
            'reference'                           => $this->getReference(),
            'add_paydata[action]'                 => 'checkout',
            'add_paydata[type]'                   => 'order',
            'add_paydata[web_url_shipping_terms]' => 'https://www.payone.com',
            'successurl'                          => 'https://www.payone.com',
            'backurl'                             => 'https://www.payone.com',
            'errorurl'                            => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
