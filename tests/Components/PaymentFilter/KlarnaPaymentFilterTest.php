<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;

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

    protected function getDisallowedCurrency(): string
    {
        return 'CZK';
    }

    protected function getAllowedCurrency(): string
    {
        return 'EUR';
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayoneKlarnaInvoicePaymentHandler::class;
    }
}
