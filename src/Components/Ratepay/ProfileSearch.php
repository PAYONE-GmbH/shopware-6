<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

class ProfileSearch
{
    /** @var string */
    private $billingCountryCode;

    /** @var string */
    private $shippingCountryCode;

    /** @var string */
    private $paymentHandler;

    /** @var string|null */
    private $salesChannelId;

    /** @var string */
    private $currency;

    /** @var bool */
    private $needsAllowDifferentAddress = false;

    /** @var float */
    private $totalAmount;

    public function getBillingCountryCode(): string
    {
        return $this->billingCountryCode;
    }

    public function setBillingCountryCode(string $billingCountryCode): void
    {
        $this->billingCountryCode = $billingCountryCode;
    }

    public function getShippingCountryCode(): string
    {
        return $this->shippingCountryCode;
    }

    public function setShippingCountryCode(string $shippingCountryCode): void
    {
        $this->shippingCountryCode = $shippingCountryCode;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }

    public function setPaymentHandler(string $paymentHandler): void
    {
        $this->paymentHandler = $paymentHandler;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function isNeedsAllowDifferentAddress(): bool
    {
        return $this->needsAllowDifferentAddress;
    }

    public function setNeedsAllowDifferentAddress(bool $needsAllowDifferentAddress): void
    {
        $this->needsAllowDifferentAddress = $needsAllowDifferentAddress;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }
}
