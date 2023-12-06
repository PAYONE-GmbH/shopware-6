<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PayonePrzelewy24PaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if ($payoneTransActionData['authorizationType'] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    public static function isRefundable(array $transactionData): bool
    {
        if ((float) $transactionData['receivable'] !== 0.0 && strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE) {
            return true;
        }

        return strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
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
