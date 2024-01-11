<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\GenericExpressCheckout\PaymentHandler\AbstractGenericExpressCheckoutPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;

class PayonePaypalExpressPaymentHandler extends AbstractGenericExpressCheckoutPaymentHandler
{
    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
