<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

class TestCredentialsStruct extends AbstractRequestParameterStruct
{
    /** @var array */
    protected $parameters = [];

    public function __construct(
        array $parameters,
        string $action = ''
    ) {
        $this->parameters = $parameters;
        $this->action     = $action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
