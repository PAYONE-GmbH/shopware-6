<?php

declare(strict_types=1);

namespace PayonePayment\Payone\PaymentHandler\ConnectionTestProvider;

use PayonePayment\HttpClient\PayoneApiClientInterface;

interface ConnectionTestProviderInterface
{
    public function testConnection(PayoneApiClientInterface $client): bool;
}
