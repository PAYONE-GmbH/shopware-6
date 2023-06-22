<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase\Mock\PaymentHandler;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;

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
}
