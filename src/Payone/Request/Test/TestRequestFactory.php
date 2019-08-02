<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Test;

use PayonePayment\Payone\Request\AbstractRequestFactory;

class TestRequestFactory extends AbstractRequestFactory
{
    public function getRequestParameters(array $parameters): array
    {
        $this->requests[] = $parameters;

        return $this->createRequest();
    }
}
