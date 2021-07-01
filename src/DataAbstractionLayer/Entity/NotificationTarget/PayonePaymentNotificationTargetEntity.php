<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationTarget;

use DateTimeInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentNotificationTargetEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $url;

    /** @var bool  */
    protected $isBasicAuth = false;

    /** @var string */
    protected $txactions;

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
}
