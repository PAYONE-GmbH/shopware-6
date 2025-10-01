<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\Checkout\Payment\PaymentException;

trait CreatePaymentExceptionTrait
{
    protected function createPaymentException(
        string $orderTransactionId,
        string $errorMessage,
        \Throwable|null $e = null,
    ): PaymentException {
        return PaymentException::asyncProcessInterrupted($orderTransactionId, $errorMessage, $e);
    }
}
