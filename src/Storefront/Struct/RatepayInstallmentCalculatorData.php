<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class RatepayInstallmentCalculatorData extends Struct
{
    final public const EXTENSION_NAME = 'payoneRatepayInstallmentCalculator';

    protected float $minimumRate;

    protected float $maximumRate;

    protected array $allowedMonths = [];

    protected array $defaults = [];

    protected array $calculationParams = [];

    protected array $calculationResponse = [];

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
