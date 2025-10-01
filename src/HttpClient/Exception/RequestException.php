<?php

declare(strict_types=1);

namespace PayonePayment\HttpClient\Exception;

class RequestException extends \Exception
{
    public function __construct(
        string $message,
        private readonly array $request = [],
    ) {
        parent::__construct($message);
    }

    public function getRequest(): array
    {
        return $this->request;
    }
}
