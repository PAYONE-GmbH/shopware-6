<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

class GetCheckoutSessionDetailsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param GetCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return array_merge(parent::getRequestParameter($arguments), [
            'add_paydata[action]' => 'getCheckoutSession',
        ]);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof GetCheckoutSessionStruct;
    }
}
