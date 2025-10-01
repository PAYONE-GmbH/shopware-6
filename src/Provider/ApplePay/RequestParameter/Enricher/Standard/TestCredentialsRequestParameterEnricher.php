<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\ApplePay\PaymentHandler\StandardPaymentHandler;
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
        // Test request for apple pay is failing because of missing token params, we will use prepayment request to validate specific merchant data

        return [
            'request'      => 'preauthorization',
            'clearingtype' => 'vor',
            'amount'       => 10000,
            'currency'     => 'EUR',
            'reference'    => $this->getReference(),
            'firstname'    => 'Test',
            'lastname'     => 'Test',
            'country'      => 'DE',
            'email'        => 'test@example.com',
            'street'       => 'teststreet 2',
            'zip'          => '12345',
            'city'         => 'Test',
            'ip'           => '127.0.0.1',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
