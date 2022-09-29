<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

class PayoneKlarnaInvoicePaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaInvoicePaymentHandler::class;
    }
}
