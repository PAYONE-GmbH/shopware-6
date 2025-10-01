<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\AuthorizationTypeEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionStatusEnum;

trait IsCapturableTrait
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        return !static::isNeverCapturable($payoneTransActionData);
    }

    /**
     * Returns true if a capture is generally not possible (or never in this context)
     * based on the current TX status notification. Use this method early in
     * isCapturable() to match common rules shared by all payment methods.
     *
     * @param array $payoneTransactionData Updated transaction data
     *
     * @return bool True if the transaction cannot be captured
     */
    final protected static function isNeverCapturable(array $payoneTransactionData): bool
    {
        $authorizationType = $payoneTransactionData['authorizationType'] ?? null;

        // Transaction types of authorization are never capturable
        return AuthorizationTypeEnum::AUTHORIZATION->value === $authorizationType;
    }

    /**
     * Returns true if a capture is possible because the TX status notification parameters
     * indicate that common defaults apply that all payment methods share. Use this method
     * as last return option in isCapturable() to match default rules shared by all
     * payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction can be captured based on matching default rules
     */
    final protected static function matchesIsCapturableDefaults(array $transactionData): bool
    {
        $txAction   = isset($transactionData['txaction']) ? \strtolower((string) $transactionData['txaction']) : null;
        $price      = isset($transactionData['price']) ? ((float) $transactionData['price']) : null;
        $receivable = isset($transactionData['receivable']) ? ((float) $transactionData['receivable']) : null;

        // Allow further captures for TX status that indicates a partial capture
        return TransactionActionEnum::CAPTURE->value === $txAction
            && \is_float($price) && \is_float($receivable)
            && $receivable > 0.0 && $receivable < $price
        ;
    }

    /**
     * Helper function to check if the transaction is appointed and completed.
     * Used in various payment handlers to check if the transaction is captureable.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction is appointed and completed
     */
    final protected static function isTransactionAppointedAndCompleted(array $transactionData): bool
    {
        $txAction = isset($transactionData['txaction'])
            ? \strtolower((string) $transactionData['txaction'])
            : null
        ;

        $transactionStatus = isset($transactionData['transaction_status'])
            ? \strtolower((string) $transactionData['transaction_status'])
            : null
        ;

        return TransactionActionEnum::APPOINTED->value === $txAction
            && TransactionStatusEnum::COMPLETED->value === $transactionStatus
        ;
    }
}
