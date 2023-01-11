<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\WeChatPay;

use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'wallettype' => 'WCP',
            'request' => self::REQUEST_ACTION_AUTHORIZE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneWeChatPayPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
