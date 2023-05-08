<?php

declare(strict_types=1);

namespace PayonePayment\Components\Currency;

use Shopware\Core\System\Currency\CurrencyEntity;

class CurrencyPrecision implements CurrencyPrecisionInterface
{
    public function getItemRoundingPrecision(CurrencyEntity $currency): int
    {
        return $currency->getItemRounding()->getDecimals();
    }

    public function getTotalRoundingPrecision(CurrencyEntity $currency): int
    {
        return $currency->getTotalRounding()->getDecimals();
    }

    public function getRoundedTotalAmount(float $amount, CurrencyEntity $currency): int
    {
        $precision = $this->getTotalRoundingPrecision($currency);

        return (int) round($amount * (10 ** $precision));
    }

    public function getRoundedItemAmount(float $price, CurrencyEntity $currency): int
    {
        $precision = $this->getItemRoundingPrecision($currency);

        return (int) round($price * (10 ** $precision));
    }
}
