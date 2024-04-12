<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase\Mock\PaymentHandler;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentException;

class PaymentHandlerMock extends AbstractPayonePaymentHandler
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        return false;
    }

    public static function isRefundable(array $transactionData): bool
    {
        return false;
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }

    protected function createPaymentException(string $orderTransactionId, string $errorMessage, ?\Throwable $e): \Throwable
    {
        if (class_exists(PaymentException::class)) {
            return PaymentException::asyncProcessInterrupted($orderTransactionId, $errorMessage, $e);
        } elseif (class_exists(SyncPaymentProcessException::class)) {
            // required for shopware version <= 6.5.3
            throw new SyncPaymentProcessException($orderTransactionId, $errorMessage, $e);  // @phpstan-ignore-line
        }

        // should never occur, just to be safe.
        throw new \RuntimeException('payment process was interrupted ' . $orderTransactionId);
    }
}
