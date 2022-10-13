<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client\Exception;

class PayoneRequestException extends \Exception
{
    private array $request;

    private array $response;

    public function __construct(string $message, array $request = [], array $response = [])
    {
        parent::__construct($message);

        $this->request = $request;
        $this->response = $response;
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
