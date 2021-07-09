<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Debit;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'clearingtype'      => self::CLEARING_TYPE_DEBIT,
            'request'           => self::REQUEST_ACTION_AUTHORIZE,
            'iban'              => $arguments->getRequestData()->get('iban', ''),
            'bic'               => $arguments->getRequestData()->get('bic', ''),
            'bankaccountholder' => $arguments->getRequestData()->get('accountOwner', ''),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
