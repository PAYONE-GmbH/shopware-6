<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService
 */
class SecuredDirectDebitPaymentFilterTest extends AbstractPaymentFilterTest
{
    protected function getFilterService(): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.secured_direct_debit');
    }

    protected function getDisallowedBillingCountry(): string
    {
        return 'CZ';
    }

    protected function getAllowedBillingCountry(): string
    {
        return 'DE';
    }

    protected function getDisallowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('CZK');

        return $currency;
    }

    protected function getAllowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        return $currency;
    }

    protected function getTooLowValue(): ?float
    {
        return 1.0;
    }

    protected function getTooHighValue(): ?float
    {
        return 2000.0;
    }

    protected function getAllowedValue(): float
    {
        return 100.0;
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayoneSecuredDirectDebitPaymentHandler::class;
    }
}
