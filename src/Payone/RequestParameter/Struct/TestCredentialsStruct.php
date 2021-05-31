<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use Shopware\Core\Framework\Struct\Struct;

class TestCredentialsStruct extends Struct
{
    use DeterminationTrait;

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

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
