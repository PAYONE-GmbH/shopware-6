<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Installer\CustomFieldInstaller;

class PayoneSecureInvoicePaymentHandler extends AbstractPayoneInvoicePaymentHandler
{
    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverCapturable($transactionData, $customFields)) {
            return false;
        }

        if (!array_key_exists(CustomFieldInstaller::AUTHORIZATION_TYPE, $customFields)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData, $customFields);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverRefundable($transactionData, $customFields)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData, $customFields);
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
