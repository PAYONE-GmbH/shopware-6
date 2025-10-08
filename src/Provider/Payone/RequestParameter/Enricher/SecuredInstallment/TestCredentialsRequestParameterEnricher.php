<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\SecuredInstallment;

use PayonePayment\Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler;
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
            'request'                            => 'preauthorization',
            'clearingtype'                       => 'fnc',
            'financingtype'                      => 'PIN',
            'mode'                               => 'test',
            'telephonenumber'                    => '49304658976',
            'birthday'                           => '20000101',
            'businessrelation'                   => 'b2c',
            'amount'                             => 30000,
            'currency'                           => 'EUR',
            'reference'                          => $this->getReference(),
            'email'                              => 'test@example.com',
            'bankaccountholder'                  => 'Test Test',
            'iban'                               => 'DE62500105171314583819',
            'add_paydata[installment_option_id]' => 'IOP_bbc08f0a1b2a41268048b41e2efb31a4',
            'firstname'                          => 'Test',
            'lastname'                           => 'Test',
            'country'                            => 'DE',
            'city'                               => 'Berlin',
            'street'                             => 'Mustergasse 5',
            'zip'                                => '10969',
            'it[1]'                              => 'goods',
            'id[1]'                              => '5013210425384',
            'pr[1]'                              => 30000,
            'de[1]'                              => 'Test product',
            'no[1]'                              => 1,
            'va[1]'                              => 19,
            'shipping_city'                      => 'Berlin',
            'shipping_country'                   => 'DE',
            'shipping_firstname'                 => 'Test',
            'shipping_lastname'                  => 'Test',
            'shipping_street'                    => 'Mustergasse 5',
            'shipping_zip'                       => '10969',
            'successurl'                         => 'https://www.payone.com',
            'errorurl'                           => 'https://www.payone.com',
            'backurl'                            => 'https://www.payone.com',
        ];
    }

    #[\Override]
    public function getPaymentHandlerIdentifier(): string
    {
        return SecuredInstallmentPaymentHandler::class;
    }
}
