<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;

class ProfileRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    /** @param RatepayProfileStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'request'                   => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]'       => 'profile',
            // ToDo: Ratepay Profile in der Administration pflegbar machen
            'add_paydata[shop_id]'      => 88880103,
            'clearingtype'              => self::CLEARING_TYPE_FINANCING,
            'financingtype'             => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof RatepayProfileStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneRatepayInstallmentPaymentHandler::class && $action === self::REQUEST_RATEPAY_PROFILE;
    }
}
