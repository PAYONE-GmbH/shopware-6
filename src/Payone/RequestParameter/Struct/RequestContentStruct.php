<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Struct\Struct;

class RequestContentStruct extends Struct {

    protected string $action;

    protected ?string $paymentMethod = null;

    protected ?float $amount;

    protected ?string $isoCode;

    protected ?string $referenceNumber;

    protected ?PaymentTransaction $paymentTransaction;

    protected ?string $workOrderId;

    protected ?CustomerAddressEntity $shippingAddress;

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    public function getIsoCode(): ?string
    {
        return $this->isoCode;
    }

    public function setIsoCode(?string $isoCode): void
    {
        $this->isoCode = $isoCode;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getPaymentTransaction(): ?PaymentTransaction
    {
        return $this->paymentTransaction;
    }

    public function setPaymentTransaction(?PaymentTransaction $paymentTransaction): void
    {
        $this->paymentTransaction = $paymentTransaction;
    }

    public function getWorkOrderId(): ?string
    {
        return $this->workOrderId;
    }

    public function setWorkOrderId(?string $workOrderId): void
    {
        $this->workOrderId = $workOrderId;
    }

    public function getShippingAddress(): ?CustomerAddressEntity
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?CustomerAddressEntity $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }
}
