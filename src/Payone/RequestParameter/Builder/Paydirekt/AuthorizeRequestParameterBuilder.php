<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Paydirekt;

use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'wallettype'   => 'PDT',
            'request'      => self::REQUEST_ACTION_AUTHORIZE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaydirektPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
