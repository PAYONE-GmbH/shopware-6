<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

interface PayonePaymentHandlerInterface
{
    /**
     * @param array $transactionData
     * @param array $customFields
     *
     * @return bool
     */
    public static function isCapturable(array $transactionData, array $customFields): bool;

    /**
     * @param array $transactionData
     * @param array $customFields
     *
     * @return bool
     */
    public static function isRefundable(array $transactionData, array $customFields): bool;
}
