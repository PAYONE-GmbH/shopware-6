<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SofortBanking;

use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        return [
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'PNT',
            // TODO: possible values DE, AT, CH, NL (this has not been implemented before)
            'bankcountry' => 'DE',
            'request'     => 'authorization',
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

        return $paymentMethod === PayoneSofortBankingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
