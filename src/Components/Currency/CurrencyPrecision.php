<?php

declare(strict_types=1);

namespace PayonePayment\Components\Currency;

use Shopware\Core\System\Currency\CurrencyEntity;

class CurrencyPrecision implements CurrencyPrecisionInterface
{
    public const DEFAULT_ROUNDING_PRECISION = 2;

    public function getItemRoundingPrecision(CurrencyEntity $currency): int
    {
        if (method_exists($currency, 'getItemRounding')) {
            return $currency->getItemRounding()->getDecimals();
        }

        if (method_exists($currency, 'getDecimalPrecision')) {
            /** @noinspection PhpDeprecationInspection */
            return $currency->getDecimalPrecision();
        }

        return self::DEFAULT_ROUNDING_PRECISION;
    }

    public function getTotalRoundingPrecision(CurrencyEntity $currency): int
    {
        if (method_exists($currency, 'getTotalRounding')) {
            return $currency->getTotalRounding()->getDecimals();
        }

        if (method_exists($currency, 'getDecimalPrecision')) {
            /** @noinspection PhpDeprecationInspection */
            return $currency->getDecimalPrecision();
        }

        return self::DEFAULT_ROUNDING_PRECISION;
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
