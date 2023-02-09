<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class SecuredInstallmentOption extends Struct
{
    protected string $installmentOptionId;

    protected float $amountValue;

    protected string $amountCurrency;

    protected float $totalAmountValue;

    protected string $totalAmountCurrency;

    protected float $monthlyAmountValue;

    protected string $monthlyAmountCurrency;

    protected float $lastRateAmountValue;

    protected string $lastRateAmountCurrency;

    protected \DateTime $firstRateDate;

    protected float $nominalInterestRate;

    protected float $effectiveInterestRate;

    protected int $numberOfPayments;

    protected string $linkCreditInformationHref;

    protected string $linkCreditInformationType;

    public function getInstallmentOptionId(): string
    {
        return $this->installmentOptionId;
    }

    public function getAmountValue(): float
    {
        return $this->amountValue;
    }

    public function getAmountCurrency(): string
    {
        return $this->amountCurrency;
    }

    public function getTotalAmountValue(): float
    {
        return $this->totalAmountValue;
    }

    public function getTotalAmountCurrency(): string
    {
        return $this->totalAmountCurrency;
    }

    public function getMonthlyAmountValue(): float
    {
        return $this->monthlyAmountValue;
    }

    public function getMonthlyAmountCurrency(): string
    {
        return $this->monthlyAmountCurrency;
    }

    public function getLastRateAmountValue(): float
    {
        return $this->lastRateAmountValue;
    }

    public function getLastRateAmountCurrency(): string
    {
        return $this->lastRateAmountCurrency;
    }

    public function getFirstRateDate(): \DateTime
    {
        return $this->firstRateDate;
    }

    public function getNominalInterestRate(): float
    {
        return $this->nominalInterestRate;
    }

    public function getEffectiveInterestRate(): float
    {
        return $this->effectiveInterestRate;
    }

    public function getNumberOfPayments(): int
    {
        return $this->numberOfPayments;
    }

    public function getLinkCreditInformationHref(): string
    {
        return $this->linkCreditInformationHref;
    }

    public function getLinkCreditInformationType(): string
    {
        return $this->linkCreditInformationType;
    }
}
