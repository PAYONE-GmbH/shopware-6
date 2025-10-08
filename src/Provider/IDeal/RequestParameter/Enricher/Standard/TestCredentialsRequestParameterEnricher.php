<?php

declare(strict_types=1);

namespace PayonePayment\Provider\IDeal\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\IDeal\PaymentHandler\StandardPaymentHandler;
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
            'request'                => 'preauthorization',
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'IDL',
            'bankcountry'            => 'NL',
            'amount'                 => 100,
            'currency'               => 'EUR',
            'reference'              => $this->getReference(),
            'firstname'              => 'Test',
            'lastname'               => 'Test',
            'country'                => 'NL',
            'successurl'             => 'https://www.payone.com',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
