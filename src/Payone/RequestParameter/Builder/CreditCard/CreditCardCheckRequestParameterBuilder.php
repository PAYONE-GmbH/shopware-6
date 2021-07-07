<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;

class CreditCardCheckRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param CreditCardCheckStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'request'       => self::REQUEST_ACTION_CREDITCARD_CHECK,
            'storecarddata' => 'yes',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof CreditCardCheckStruct)) {
            return false;
        }

        return true;
    }
}
