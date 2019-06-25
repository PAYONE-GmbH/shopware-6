<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentCardEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $pseudoCardPan;

    /** @var string */
    protected $truncatedCardPan;

    /** @var null|CustomerEntity */
    protected $customer;

    /** @var string */
    protected $customerId;

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
