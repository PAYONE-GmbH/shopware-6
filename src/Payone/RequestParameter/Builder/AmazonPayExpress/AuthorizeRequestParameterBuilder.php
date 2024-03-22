<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Amazon\AbstractAmazonRequestParameterBuilder;
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
            'request' => $arguments->getAction(),
            'clearingtype' => AbstractAmazonRequestParameterBuilder::CLEARING_TYPE,
            'wallettype' => AbstractAmazonRequestParameterBuilder::WALLET_TYPE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        $action = $arguments->getAction();

        return $arguments instanceof PaymentTransactionStruct
            && $arguments->getPaymentMethod() === PayoneAmazonPayExpressPaymentHandler::class
            && ($action === self::REQUEST_ACTION_AUTHORIZE || $action === self::REQUEST_ACTION_PREAUTHORIZE);
    }
}
