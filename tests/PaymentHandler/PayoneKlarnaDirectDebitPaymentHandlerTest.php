<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

class PayoneKlarnaDirectDebitPaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaDirectDebitPaymentHandler::class;
    }
}
