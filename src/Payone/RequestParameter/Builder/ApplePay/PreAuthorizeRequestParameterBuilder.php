<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\ApplePay;

use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\ApplePayTransactionStruct;

class PreAuthorizeRequestParameterBuilder extends AuthorizeRequestParameterBuilder
{
    /** @param ApplePayTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return array_merge(parent::getRequestParameter($arguments), [
            'request' => self::REQUEST_ACTION_PREAUTHORIZE,
        ]);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof ApplePayTransactionStruct)) {
            return false;
        }

        return $arguments->getAction() === self::REQUEST_ACTION_PREAUTHORIZE;
    }
}
