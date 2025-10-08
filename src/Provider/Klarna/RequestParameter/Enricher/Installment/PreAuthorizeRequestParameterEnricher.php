<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\RequestParameter\Enricher\Installment;

use PayonePayment\Payone\Request\RequestActionEnum;

readonly class PreAuthorizeRequestParameterEnricher extends AuthorizeRequestParameterEnricher
{
    #[\Override]
    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::PREAUTHORIZE;
    }
}
