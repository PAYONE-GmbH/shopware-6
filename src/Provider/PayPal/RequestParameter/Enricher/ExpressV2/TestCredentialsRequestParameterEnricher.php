<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\RequestParameter\Enricher\ExpressV2;

use PayonePayment\Provider\PayPal\PaymentHandler\ExpressV2PaymentHandler;
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
            'request'            => 'preauthorization',
            'clearingtype'       => 'wlt',
            'wallettype'         => 'PAL',
            'amount'             => 100,
            'currency'           => 'EUR',
            'reference'          => $this->getReference(),
            'firstname'          => 'Test',
            'lastname'           => 'Test',
            'country'            => 'DE',
            'successurl'         => 'https://www.payone.com',
            'errorurl'           => 'https://www.payone.com',
            'backurl'            => 'https://www.payone.com',
            'shipping_city'      => 'Berlin',
            'shipping_country'   => 'DE',
            'shipping_firstname' => 'Test',
            'shipping_lastname'  => 'Test',
            'shipping_street'    => 'Mustergasse 5',
            'shipping_zip'       => '10969',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return ExpressV2PaymentHandler::class;
    }
}
