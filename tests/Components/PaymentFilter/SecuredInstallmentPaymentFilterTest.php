<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService
 */
class SecuredInstallmentPaymentFilterTest extends AbstractPaymentFilterTest
{
    protected function getFilterService(?string $paymentHandlerClass = null): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.secured_installment');
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
        return 150.0;
    }

    protected function getTooHighValue(): ?float
    {
        return 3600.0;
    }

    protected function getAllowedValue(): float
    {
        return 300.0;
    }

    protected function getPaymentHandlerClasses(): array
    {
        return [PayoneSecuredInstallmentPaymentHandler::class];
    }
}
