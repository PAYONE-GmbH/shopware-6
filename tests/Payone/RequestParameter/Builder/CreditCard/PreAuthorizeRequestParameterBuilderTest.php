<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\CreditCard\AuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends AuthorizeRequestParameterBuilderTest
{
    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
