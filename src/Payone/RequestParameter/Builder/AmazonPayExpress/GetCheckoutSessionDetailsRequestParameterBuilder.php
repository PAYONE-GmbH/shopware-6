<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Amazon\AbstractAmazonRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

class GetCheckoutSessionDetailsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param GetCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'add_paydata[action]' => 'getCheckoutSession',
            'clearingtype' => AbstractAmazonRequestParameterBuilder::CLEARING_TYPE,
            'wallettype' => AbstractAmazonRequestParameterBuilder::WALLET_TYPE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof GetCheckoutSessionStruct
            && $arguments->getPaymentMethod() === PayoneAmazonPayExpressPaymentHandler::class;
    }
}
