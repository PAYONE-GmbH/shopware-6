<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService
 */
class Przelewy24PaymentFilterTest extends AbstractPaymentFilterTest
{
    protected function getFilterService(): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.przelewy24');
    }

    protected function getDisallowedBillingCountry(): string
    {
        return 'DE';
    }

    protected function getAllowedBillingCountry(): string
    {
        return 'PL';
    }

    protected function getDisallowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        return $currency;
    }

    protected function getAllowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('PLN');

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
        return PayonePrzelewy24PaymentHandler::class;
    }
}
