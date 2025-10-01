<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\ExpressCheckout;

use PayonePayment\RequestParameter\RequestParameterEnricherChain;

readonly class ExpressCheckoutSessionEnricherChainsDto
{
    public function __construct(
        public RequestParameterEnricherChain $createEnricherChain,
        public RequestParameterEnricherChain $getRequestEnricherChain,
        public RequestParameterEnricherChain $updateRequestEnricherChain,
    ) {
    }
}
