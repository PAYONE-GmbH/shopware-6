<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\RequestParameter\Enricher\Wallet;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\PostFinance\Enum\TransferTypeEnum;
use PayonePayment\Provider\PostFinance\PaymentHandler\WalletPaymentHandler;
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
            'request'                => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'    => 'register_alias',
            'clearingtype'           => PayoneClearingEnum::ONLINE_BANK_TRANSFER->value,
            'onlinebanktransfertype' => TransferTypeEnum::WALLET->value,
            'bankcountry'            => 'CH',
            'amount'                 => 100,
            'currency'               => 'CHF',
            'reference'              => $this->getReference(),
            'lastname'               => 'Test',
            'country'                => 'CH',
            'successurl'             => 'https://www.payone.com',
            'errorurl'               => 'https://www.payone.com',
            'backurl'                => 'https://www.payone.com',
        ];
    }

    public function getPaymentHandlerIdentifier(): string
    {
        return WalletPaymentHandler::class;
    }
}
