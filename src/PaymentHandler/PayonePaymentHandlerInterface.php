<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

interface PayonePaymentHandlerInterface
{
    /**
     * Called from the administration controllers to verify if a transaction can be captured.
     */
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool;

    /**
     * Called from the administration controllers to verify if a transaction can be refunded.
     */
    public static function isRefundable(array $transactionData): bool;
}
