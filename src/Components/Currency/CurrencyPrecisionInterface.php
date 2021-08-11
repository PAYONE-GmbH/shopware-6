<?php

declare(strict_types=1);

namespace PayonePayment\Components\Currency;

use Shopware\Core\System\Currency\CurrencyEntity;

interface CurrencyPrecisionInterface
{
    public function getItemRoundingPrecision(CurrencyEntity $currency): int;

    public function getTotalRoundingPrecision(CurrencyEntity $currency): int;
}
