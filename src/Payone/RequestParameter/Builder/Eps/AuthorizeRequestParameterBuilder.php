<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Eps;

use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        AbstractRequestParameterStruct $arguments
    ): array {
        $dataBag = $arguments->getRequestData();

        return [
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'EPS',
            'bankcountry'            => 'AT',
            'bankgrouptype'          => $dataBag->get('epsBankGroup'),
            'request'                => 'authorization',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneEpsPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
