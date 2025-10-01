<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client\Exception;

/**
 * @deprecated Use more detailled exeption from \PayonePayment\Payone\HttpClient\Exception\*Exception
 */
class PayoneRequestException extends \Exception
{
    public function __construct(
        string $message,
        private readonly array $request = [],
        private readonly array $response = [],
    ) {
        parent::__construct($message);
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
