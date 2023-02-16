<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService
 */
class SofortPaymentFilterTest extends AbstractPaymentFilterTest
{
    protected function getFilterService(): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.sofort');
    }

    protected function getDisallowedBillingCountry(): string
    {
        return 'US';
    }

    protected function getAllowedBillingCountry(): string
    {
        return 'DE';
    }

    protected function getDisallowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('USD');

        return $currency;
    }

    protected function getAllowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        return $currency;
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayoneSofortBankingPaymentHandler::class;
    }
}
