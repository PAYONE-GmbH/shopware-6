<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalV2Express;

use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

class CreateCheckoutSessionParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param CreateExpressCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'add_paydata[action]' => 'setexpresscheckout',
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'wallettype' => 'PAL',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof CreateExpressCheckoutSessionStruct
            && $arguments->getPaymentMethod() === PayonePaypalV2ExpressPaymentHandler::class;
    }
}
