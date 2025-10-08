<?php

declare(strict_types=1);

namespace PayonePayment\Provider\SofortBanking\RequestParameter\Enricher\Standard;

use PayonePayment\Provider\SofortBanking\PaymentHandler\StandardPaymentHandler;
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
            'onlinebanktransfertype' => 'PNT',
            'bankcountry'            => 'DE',
            'amount'                 => 100,
            'currency'               => 'EUR',
            'reference'              => $this->getReference(),
            'firstname'              => 'Test',
            'lastname'               => 'Test',
            'country'                => 'DE',
            'successurl'             => 'https://www.payone.com',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return StandardPaymentHandler::class;
    }
}
