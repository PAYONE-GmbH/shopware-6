<?php

declare(strict_types=1);

namespace PayonePayment\Payone\PaymentHandler;

use PayonePayment\Payone\PaymentHandler\ConnectionTestProvider\ConnectionTestProviderInterface;

interface ConnectionTestProviderAwareInterface
{
    public function getConnectionTestProvider(): ConnectionTestProviderInterface;
}
