<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\RequestParameter\Enricher\Installment;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Klarna\PaymentHandler\InstallmentPaymentHandler;
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
            'request'             => RequestActionEnum::GENERIC_PAYMENT->value,
            'clearingtype'        => PayoneClearingEnum::FINANCING->value,
            'amount'              => 100,
            'country'             => 'DE',
            'currency'            => 'EUR',
            'add_paydata[action]' => 'start_session',
            'it[1]'               => 'goods',
            'id[1]'               => '5013210425384',
            'pr[1]'               => 100,
            'de[1]'               => 'Test product',
            'no[1]'               => 1,
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return InstallmentPaymentHandler::class;
    }
}
