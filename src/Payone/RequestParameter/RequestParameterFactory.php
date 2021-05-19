<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;

class RequestParameterFactory {
    /** @var iterable<AbstractRequestParameterBuilder> */
    private $requestParameterBuilder;

    public function __construct(iterable $requestParameterBuilder) {
        $this->requestParameterBuilder = $requestParameterBuilder;
    }

    public function getRequestParameter() : array {
        //TODO: get builder
        return [];
    }
}
