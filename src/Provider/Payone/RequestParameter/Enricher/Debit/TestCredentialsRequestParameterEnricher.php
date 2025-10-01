<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\Debit;

use PayonePayment\Provider\Payone\PaymentHandler\DebitPaymentHandler;
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
            'request'           => 'preauthorization',
            'clearingtype'      => 'elv',
            'iban'              => 'DE00123456782599100003',
            'bic'               => 'TESTTEST',
            'bankaccountholder' => 'Test Test',
            'amount'            => 100,
            'currency'          => 'EUR',
            'reference'         => $this->getReference(),
            'firstname'         => 'Test',
            'lastname'          => 'Test',
            'country'           => 'DE',
            'successurl'        => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return DebitPaymentHandler::class;
    }
}
