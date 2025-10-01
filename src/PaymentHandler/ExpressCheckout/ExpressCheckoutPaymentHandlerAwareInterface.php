<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\ExpressCheckout;

interface ExpressCheckoutPaymentHandlerAwareInterface
{
    public function getExpressCheckoutSessionEnricherChains(): ExpressCheckoutSessionEnricherChainsDto;
}
