<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\Amazon\AbstractAmazonRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

abstract class AbstractRequestParameterBuilder extends \PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder
{
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'clearingtype' => AbstractAmazonRequestParameterBuilder::CLEARING_TYPE,
            'wallettype' => AbstractAmazonRequestParameterBuilder::WALLET_TYPE,
            'add_paydata[platform_id]' => AbstractAmazonRequestParameterBuilder::PLATFORM_ID,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments->getPaymentMethod() === PayoneAmazonPayExpressPaymentHandler::class;
    }
}
