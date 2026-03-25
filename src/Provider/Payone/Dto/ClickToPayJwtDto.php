<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Dto;

readonly class ClickToPayJwtDto implements \JsonSerializable
{
    public \DateTimeImmutable $creationDate;

    public \DateTimeImmutable $expirationDate;

    public function __construct(
        string $creationDate,
        string $expirationDate,
        public string $status,
        public string $token,
    ) {
        $this->creationDate   = new \DateTimeImmutable($creationDate, new \DateTimeZone('UTC'));
        $this->expirationDate = new \DateTimeImmutable($expirationDate, new \DateTimeZone('UTC'));
    }

    public function jsonSerialize(): array
    {
        return [
            'creationDate'   => $this->creationDate->format('Y-m-d\TH:i:s.v\Z'),
            'expirationDate' => $this->expirationDate->format('Y-m-d\TH:i:s.v\Z'),
            'status'         => $this->status,
            'token'          => $this->token,
        ];
    }
}