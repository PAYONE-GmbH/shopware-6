<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalV2Express;

use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\PaypalV2\AuthorizeRequestParameterBuilder as PaypalV2AuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends PaypalV2AuthorizeRequestParameterBuilder
{
    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayonePaypalV2ExpressPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
