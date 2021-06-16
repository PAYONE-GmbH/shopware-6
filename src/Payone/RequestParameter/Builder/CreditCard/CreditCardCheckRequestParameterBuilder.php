<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;
use Shopware\Core\Framework\Struct\Struct;

class CreditCardCheckRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param CreditCardCheckStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        return [
            'request'       => 'creditcardcheck',
            'storecarddata' => 'yes',
        ];
    }

    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof CreditCardCheckStruct)) {
            return false;
        }

        return true;
    }
}
