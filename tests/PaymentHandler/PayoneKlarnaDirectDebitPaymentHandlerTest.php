<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler
 */
class PayoneKlarnaDirectDebitPaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaDirectDebitPaymentHandler::class;
    }
}
