<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Prepayment;

use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class PreAuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'request'      => self::REQUEST_ACTION_PREAUTHORIZE,
            'clearingtype' => self::CLEARING_TYPE_PREPAYMENT,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        return $paymentMethod === PayonePrepaymentPaymentHandler::class;
    }
}
