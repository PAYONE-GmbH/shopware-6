<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;

class TestCredentialsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param TestCredentialsStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return $arguments->getParameters();
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof TestCredentialsStruct)) {
            return false;
        }

        $action = $arguments->getAction();

        return $action === self::REQUEST_ACTION_TEST;
    }
}
