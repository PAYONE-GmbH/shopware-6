<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\Prepayment;

use PayonePayment\Provider\Payone\PaymentHandler\PrepaymentPaymentHandler;
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
        return PrepaymentPaymentHandler::class;
    }
}
