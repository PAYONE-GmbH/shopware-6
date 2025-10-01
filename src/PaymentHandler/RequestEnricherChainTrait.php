<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\RequestParameter\RequestParameterEnricherChain;

trait RequestEnricherChainTrait
{
    protected readonly RequestParameterEnricherChain $requestEnricherChain;

    public function getRequestEnricherChain(): RequestParameterEnricherChain
    {
        return $this->requestEnricherChain;
    }
}
