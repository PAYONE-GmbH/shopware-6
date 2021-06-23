<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalExpress;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class PreAuthorizeRequestParameterBuilder extends AuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        AbstractRequestParameterStruct $arguments
    ): array {
        return array_merge(parent::getRequestParameter($arguments), [
            'request' => 'preauthorization',
        ]);
    }

    public function AbstractRequestParameterStruct(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaypalExpressPaymentHandler::class && $action === self::REQUEST_ACTION_PREAUTHORIZE;
    }
}
