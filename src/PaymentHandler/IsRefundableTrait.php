<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;

trait IsRefundableTrait
{
    public static function isRefundable(array $transactionData): bool
    {
        return static::matchesIsRefundableDefaults($transactionData);
    }

    /**
     * Returns true if a refund is possible because the TX status notification parameters
     * indicate that common defaults apply that all payment methods share. Use this method
     * as last return option in isRefundable() to match default rules shared by all
     * payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction can be refunded based on matching default rules
     */
    final protected static function matchesIsRefundableDefaults(array $transactionData): bool
    {
        $txAction   = isset($transactionData['txaction']) ? \strtolower((string) $transactionData['txaction']) : null;
        $receivable = isset($transactionData['receivable']) ? ((float) $transactionData['receivable']) : null;

        // Allow refund if capture TX status and receivable indicate we have outstanding funds
        if (TransactionActionEnum::CAPTURE->value === $txAction && $receivable > 0.0) {
            return true;
        }

        // If an incoming debit TX status indicates a partial refund we allow further refunds
        if (TransactionActionEnum::DEBIT->value === $txAction && $receivable > 0.0) {
            return true;
        }

        // We got paid and that means we can refund
        if (TransactionActionEnum::PAID->value === $txAction) {
            return true;
        }

        return false;
    }
}
