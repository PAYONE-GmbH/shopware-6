<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use Shopware\Core\System\Currency\CurrencyEntity;

class CurrencyPrecisionService
{
    public function getRoundedTotalAmount(float $amount, CurrencyEntity $currency): int
    {
        return (int) \round($amount * (10 ** $currency->getTotalRounding()->getDecimals()));
    }

    public function getRoundedItemAmount(float $price, CurrencyEntity $currency): int
    {
        return (int) \round($price * (10 ** $currency->getItemRounding()->getDecimals()));
    }
}
