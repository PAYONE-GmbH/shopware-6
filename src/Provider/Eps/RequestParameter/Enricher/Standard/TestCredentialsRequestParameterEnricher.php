<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Eps\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\Eps\PaymentHandler\StandardPaymentHandler;
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
            'onlinebanktransfertype' => 'EPS',
            'bankcountry'            => 'AT',
            'bankgrouptype'          => 'ARZ_HTB',
            'amount'                 => 100,
            'currency'               => 'EUR',
            'reference'              => $this->getReference(),
            'firstname'              => 'Test',
            'lastname'               => 'Test',
            'country'                => 'AT',
            'successurl'             => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
