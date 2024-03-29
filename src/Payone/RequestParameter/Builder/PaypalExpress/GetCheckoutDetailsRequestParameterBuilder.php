<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalExpress;

use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

class GetCheckoutDetailsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param GetCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'add_paydata[action]' => 'getexpresscheckoutdetails',
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'wallettype' => 'PPE',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof GetCheckoutSessionStruct
            && $arguments->getPaymentMethod() === PayonePaypalExpressPaymentHandler::class;
    }
}
