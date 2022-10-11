<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;

interface LineItemHydratorInterface
{
    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderEntity $order,
        array $requestLines,
        bool $includeShippingCosts
    ): array;

    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, Context $context): array;
}
