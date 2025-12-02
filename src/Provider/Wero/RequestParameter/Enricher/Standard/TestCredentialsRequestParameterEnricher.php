<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Wero\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\Wero\PaymentHandler\StandardPaymentHandler;
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
            'request'      => 'preauthorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'WRO',
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

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
