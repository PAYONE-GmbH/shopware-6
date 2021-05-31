<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use Shopware\Core\Framework\Struct\Struct;

class TestCredentialsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param TestCredentialsStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        return $arguments->getParameters();
    }

    /** @param TestCredentialsStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof TestCredentialsStruct)) {
            return false;
        }

        $action = $arguments->getAction();

        if ($action === self::REQUEST_ACTION_TEST) {
            return true;
        }

        return false;
    }
}
