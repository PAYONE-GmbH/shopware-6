<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

class TestCredentialsStruct extends AbstractRequestParameterStruct
{
    /** @var array */
    protected $parameters = [];

    public function __construct(
        array $parameters,
        string $action = '',
        string $paymentMethodClass = null
    ) {
        $this->parameters    = $parameters;
        $this->action        = $action;
        $this->paymentMethod = $paymentMethodClass;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
