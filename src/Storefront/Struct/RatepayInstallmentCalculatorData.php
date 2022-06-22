<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class RatepayInstallmentCalculatorData extends Struct
{
    public const EXTENSION_NAME = 'payoneRatepayInstallmentCalculator';

    /** @var float */
    protected $minimumRate;

    /** @var float */
    protected $maximumRate;

    /** @var array */
    protected $allowedMonths = [];

    /** @var string */
    protected $debitPayType;

    /** @var array */
    protected $defaults = [];

    /** @var array */
    protected $calculationParams = [];

    /** @var array */
    protected $calculationResponse = [];

    public function getMinimumRate(): float
    {
        return $this->minimumRate;
    }

    public function getMaximumRate(): float
    {
        return $this->maximumRate;
    }

    public function getAllowedMonths(): array
    {
        return $this->allowedMonths;
    }

    public function getDebitPayType(): string
    {
        return $this->debitPayType;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getCalculationParams(): array
    {
        return $this->calculationParams;
    }

    public function getCalculationResponse(): array
    {
        return $this->calculationResponse;
    }
}
