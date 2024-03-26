<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return array_merge(parent::getRequestParameter($arguments), [
            'request' => $arguments->getAction(),
        ]);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        $action = $arguments->getAction();

        return parent::supports($arguments)
            && $arguments instanceof PaymentTransactionStruct
            && ($action === self::REQUEST_ACTION_AUTHORIZE || $action === self::REQUEST_ACTION_PREAUTHORIZE);
    }
}
