<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\CreditCard;

use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;
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
            'request'        => 'preauthorization',
            'clearingtype'   => 'cc',
            'amount'         => 100,
            'currency'       => 'EUR',
            'reference'      => $this->getReference(),
            'cardpan'        => '5500000000000004',
            'pseudocardpan'  => '5500000000099999',
            'cardtype'       => 'M',
            'cardexpiredate' => (new \DateTimeImmutable())->add(new \DateInterval('P1Y'))->format('ym'),
            'ecommercemode'  => 'internet',
            'firstname'      => 'Test',
            'lastname'       => 'Test',
            'country'        => 'DE',
            'successurl'     => 'https://www.payone.com',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return CreditCardPaymentHandler::class;
    }
}
