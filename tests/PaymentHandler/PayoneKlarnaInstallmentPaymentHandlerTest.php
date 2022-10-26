<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler
 */
class PayoneKlarnaInstallmentPaymentHandlerTest extends AbstractKlarnaPaymentHandlerTest
{
    protected function getKlarnaPaymentHandler(): string
    {
        return PayoneKlarnaInstallmentPaymentHandler::class;
    }
}
