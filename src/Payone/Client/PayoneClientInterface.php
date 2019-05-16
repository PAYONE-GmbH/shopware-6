<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;

interface PayoneClientInterface
{
    /**
     * @param array $parameters
     *
     * @throws PayoneRequestException
     *
     * @return array
     */
    public function request(array $parameters): array;
}
