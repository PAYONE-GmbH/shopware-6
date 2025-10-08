<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Alipay\RequestParameter\Enricher;

use PayonePayment\Payone\Request\RequestActionEnum;

readonly class PreAuthorizeRequestParameterEnricher extends AuthorizeRequestParameterEnricher
{
    #[\Override]
    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::PREAUTHORIZE;
    }
}
