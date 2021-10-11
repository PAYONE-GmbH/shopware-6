<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationTarget;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentNotificationTargetEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $url;

    /** @var bool */
    protected $isBasicAuth = false;

    /** @var string */
    protected $txactions;

    /** @var null|string */
    protected $username;

    /** @var null|string */
    protected $password;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function isBasicAuth(): bool
    {
        return $this->isBasicAuth;
    }

    public function setIsBasicAuth(bool $isBasicAuth): void
    {
        $this->isBasicAuth = $isBasicAuth;
    }

    public function getTxactions(): string
    {
        return $this->txactions;
    }

    public function setTxactions(string $txactions): void
    {
        $this->txactions = $txactions;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
