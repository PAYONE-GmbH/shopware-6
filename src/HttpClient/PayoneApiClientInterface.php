<?php

declare(strict_types=1);

namespace PayonePayment\HttpClient;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;

interface PayoneApiClientInterface
{
    /**
     * @throws PayoneRequestException
     */
    public function request(array $parameters, bool $expectsJson = true): array;
}
