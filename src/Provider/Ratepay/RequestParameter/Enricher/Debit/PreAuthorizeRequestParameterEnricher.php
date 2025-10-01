<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Debit;

use PayonePayment\Payone\Request\RequestActionEnum;

readonly class PreAuthorizeRequestParameterEnricher extends AuthorizeRequestParameterEnricher
{
    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::PREAUTHORIZE;
    }
}
