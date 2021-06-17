<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\IDeal;

use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $dataBag = $arguments->getRequestData();

        return [
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'IDL',
            'bankcountry'            => 'NL',
            'bankgrouptype'          => $dataBag->get('idealBankGroup'),
            'request'                => 'authorization',
        ];
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneIDealPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
