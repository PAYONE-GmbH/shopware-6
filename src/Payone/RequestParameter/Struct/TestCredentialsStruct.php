<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

class TestCredentialsStruct extends AbstractRequestParameterStruct
{
    public function __construct(
        protected array $parameters,
        string $action = '',
        string $paymentMethodClass = '',
    ) {
        $this->action        = $action;
        $this->paymentMethod = $paymentMethodClass;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
