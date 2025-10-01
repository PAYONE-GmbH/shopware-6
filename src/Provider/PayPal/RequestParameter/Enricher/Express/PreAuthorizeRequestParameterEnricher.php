<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\RequestParameter\Enricher\Express;

use PayonePayment\Payone\Request\RequestActionEnum;

readonly class PreAuthorizeRequestParameterEnricher extends AuthorizeRequestParameterEnricher
{
    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::PREAUTHORIZE;
    }
}
