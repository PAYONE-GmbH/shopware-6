<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Alipay\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\Alipay\PaymentHandler\StandardPaymentHandler;
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
            'request'      => 'preauthorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'ALP',
            'amount'       => 100,
            'country'      => 'DE',
            'currency'     => 'EUR',
            'reference'    => $this->getReference(),
            'lastname'     => 'Test',
            'successurl'   => 'https://www.payone.com',
            'errorurl'     => 'https://www.payone.com',
            'backurl'      => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
