<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

class PayoneKlarnaInstallmentPaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaInstallmentPaymentHandler::class;
    }
}
