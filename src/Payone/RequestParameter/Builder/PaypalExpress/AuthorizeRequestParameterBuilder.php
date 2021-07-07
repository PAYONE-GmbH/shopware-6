<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalExpress;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\Paypal\AuthorizeRequestParameterBuilder as PaypalAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends PaypalAuthorizeRequestParameterBuilder
{
    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaypalExpressPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
