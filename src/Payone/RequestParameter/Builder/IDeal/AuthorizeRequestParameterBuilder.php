<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\IDeal;

use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();

        return [
            'clearingtype'           => self::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
            'onlinebanktransfertype' => 'IDL',
            'bankcountry'            => 'NL',
            'bankgrouptype'          => $dataBag->get('idealBankGroup'),
            'request'                => self::REQUEST_ACTION_AUTHORIZE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneIDealPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
