<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Invoice;

use PayonePayment\Provider\Payolution\PaymentHandler\InvoicePaymentHandler;
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
            'request'                   => 'genericpayment',
            'clearingtype'              => 'fnc',
            'financingtype'             => 'PYV',
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Invoicing',
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
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return InvoicePaymentHandler::class;
    }
}
