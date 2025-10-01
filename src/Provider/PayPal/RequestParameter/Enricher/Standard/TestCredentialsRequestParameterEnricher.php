<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\PayPal\PaymentHandler\StandardPaymentHandler;
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
            'wallettype'   => 'PPE',
            'amount'       => 100,
            'currency'     => 'EUR',
            'reference'    => $this->getReference(),
            'firstname'    => 'Test',
            'lastname'     => 'Test',
            'country'      => 'DE',
            'successurl'   => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
