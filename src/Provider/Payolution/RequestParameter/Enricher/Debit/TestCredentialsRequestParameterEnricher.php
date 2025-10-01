<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Debit;

use PayonePayment\Provider\Payolution\PaymentHandler\DebitPaymentHandler;
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
            'request'                   => 'genericpayment',
            'clearingtype'              => 'fnc',
            'financingtype'             => 'PYD',
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Debit',
            'amount'                    => 10000,
            'currency'                  => 'EUR',
            'reference'                 => $this->getReference(),
            'birthday'                  => '19900505',
            'firstname'                 => 'Test',
            'lastname'                  => 'Test',
            'country'                   => 'DE',
            'email'                     => 'test@example.com',
            'street'                    => 'teststreet 2',
            'zip'                       => '12345',
            'city'                      => 'Test',
            'ip'                        => '127.0.0.1',
            'iban'                      => 'DE00123456782599100004',
            'bic'                       => 'TESTTEST',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return DebitPaymentHandler::class;
    }
}
