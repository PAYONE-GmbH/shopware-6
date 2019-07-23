<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;

interface PayoneClientInterface
{
    /**
     * @throws PayoneRequestException
     */
    public function request(array $parameters, bool $json = true): array;
}
