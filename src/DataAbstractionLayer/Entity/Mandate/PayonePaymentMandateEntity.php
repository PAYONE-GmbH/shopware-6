<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Mandate;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentMandateEntity extends Entity
{
    use EntityIdTrait;

    protected string $identification;

    protected \DateTimeInterface $signatureDate;

    protected ?CustomerEntity $customer = null;

    protected string $customerId;

    public function getIdentification(): string
    {
        return $this->identification;
    }

    public function setIdentification(string $identification): void
    {
        $this->identification = $identification;
    }

    public function getSignatureDate(): \DateTimeInterface
    {
        return $this->signatureDate;
    }

    public function setSignatureDate(\DateTimeInterface $signatureDate): void
    {
        $this->signatureDate = $signatureDate;
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
