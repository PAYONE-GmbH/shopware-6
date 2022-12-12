<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\KlarnaPaymentMethodFilter
 */
class KlarnaPaymentFilterTest extends AbstractPaymentFilterTest
{
    protected function getFilterService(): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.klarna');
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
        return null;
    }

    protected function getTooHighValue(): ?float
    {
        return null;
    }

    protected function getAllowedValue(): float
    {
        return 100.0;
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayoneKlarnaInvoicePaymentHandler::class;
    }
}
