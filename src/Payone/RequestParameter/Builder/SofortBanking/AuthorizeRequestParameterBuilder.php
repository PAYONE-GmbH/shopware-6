<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SofortBanking;

use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'clearingtype'           => self::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
            'onlinebanktransfertype' => 'PNT',
            // TODO: possible values DE, AT, CH, NL (this has not been implemented before)
            'bankcountry' => 'DE',
            'request'     => self::REQUEST_ACTION_AUTHORIZE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneSofortBankingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
