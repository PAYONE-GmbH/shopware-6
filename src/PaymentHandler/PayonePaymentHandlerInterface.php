<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

interface PayonePaymentHandlerInterface
{
    public static function isCapturable(array $transactionData, array $customFields): bool;

    public static function isRefundable(array $transactionData, array $customFields): bool;
}
