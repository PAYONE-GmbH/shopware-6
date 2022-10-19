<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

use Symfony\Component\HttpFoundation\ParameterBag;

trait RequestDataTrait
{
    protected ParameterBag $requestData;

    public function getRequestData(): ParameterBag
    {
        return $this->requestData;
    }
}
