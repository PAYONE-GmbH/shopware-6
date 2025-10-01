<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Przelewy24\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\Przelewy24\PaymentHandler\StandardPaymentHandler;
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
            'request'                => 'preauthorization',
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'P24',
            'bankcountry'            => 'PL',
            'amount'                 => 100,
            'currency'               => 'EUR',
            'reference'              => $this->getReference(),
            'lastname'               => 'Test',
            'country'                => 'PL',
            'successurl'             => 'https://www.payone.com',
            'errorurl'               => 'https://www.payone.com',
            'backurl'                => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
