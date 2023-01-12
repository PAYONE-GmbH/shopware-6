<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

/**
 * @covers \PayonePayment\PaymentHandler\PayonePostfinanceCardPaymentHandler
 */
class PayonePostfinanceCardPaymentHandlerTest extends AbstractPostfinancePaymentHandlerTest
{
    protected function getPostfinancePaymentHandler(): string
    {
        return PayonePostfinanceCardPaymentHandler::class;
    }
}
