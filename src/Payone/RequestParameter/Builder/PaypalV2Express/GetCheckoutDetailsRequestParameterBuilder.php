<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalV2Express;

use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;
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
            'wallettype' => 'PAL',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof GetCheckoutSessionStruct
            && $arguments->getPaymentMethod() === PayonePaypalV2ExpressPaymentHandler::class;
    }
}
