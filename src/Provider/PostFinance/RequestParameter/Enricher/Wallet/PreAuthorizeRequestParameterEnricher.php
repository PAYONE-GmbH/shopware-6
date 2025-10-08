<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\RequestParameter\Enricher\Wallet;

use PayonePayment\Payone\Request\RequestActionEnum;

readonly class PreAuthorizeRequestParameterEnricher extends AuthorizeRequestParameterEnricher
{
    #[\Override]
    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::PREAUTHORIZE;
    }
}
