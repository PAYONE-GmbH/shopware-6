<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PayonePrzelewy24PaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'transactionState' => $response['status'],
            'allowCapture' => false,
            'allowRefund' => false,
        ];
    }
}
