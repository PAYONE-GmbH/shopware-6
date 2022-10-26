<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

abstract class AbstractKlarnaParameterBuilder extends AbstractRequestParameterBuilder
{
    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return is_subclass_of($arguments->getPaymentMethod(), AbstractKlarnaPaymentHandler::class);
    }
}
