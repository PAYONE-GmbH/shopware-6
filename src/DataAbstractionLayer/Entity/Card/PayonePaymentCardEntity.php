<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentCardEntity extends Entity
{
    use EntityIdTrait;

    protected string $pseudoCardPan;

    protected string $truncatedCardPan;

    protected string $cardType;

    protected \DateTimeInterface $expiresAt;

    protected ?CustomerEntity $customer = null;

    protected string $customerId;

    public function getPseudoCardPan(): string
    {
        return $this->pseudoCardPan;
    }

    public function setPseudoCardPan(string $pseudoCardPan): void
    {
        $this->pseudoCardPan = $pseudoCardPan;
    }

    public function getTruncatedCardPan(): string
    {
        return $this->truncatedCardPan;
    }

    public function setTruncatedCardPan(string $truncatedCardPan): void
    {
        $this->truncatedCardPan = $truncatedCardPan;
    }

    public function getCardType(): string
    {
        return $this->cardType;
    }

    public function setCardType(string $cardType): void
    {
        $this->cardType = $cardType;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}
