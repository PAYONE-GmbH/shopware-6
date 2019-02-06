<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client;

interface PayoneClientInterface
{
    public function request(array $parameters): array;
}
