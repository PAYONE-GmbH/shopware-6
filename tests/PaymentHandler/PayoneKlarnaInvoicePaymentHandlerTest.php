<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler
 */
class PayoneKlarnaInvoicePaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaInvoicePaymentHandler::class;
    }
}
