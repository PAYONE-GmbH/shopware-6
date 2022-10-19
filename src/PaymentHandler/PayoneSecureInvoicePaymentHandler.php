<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

class PayoneSecureInvoicePaymentHandler extends AbstractPayoneInvoicePaymentHandler
{
    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigKey(): string
    {
        return 'secureInvoiceAuthorizationMethod';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentMethod(): string
    {
        return __CLASS__;
    }
}
