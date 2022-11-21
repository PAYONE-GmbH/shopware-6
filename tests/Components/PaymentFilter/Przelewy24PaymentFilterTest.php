<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;

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

    protected function getDisallowedCurrency(): string
    {
        return 'EUR';
    }

    protected function getAllowedCurrency(): string
    {
        return 'PLN';
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayonePrzelewy24PaymentHandler::class;
    }
}
