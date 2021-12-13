<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface LineItemHydratorInterface
{
    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderEntity $order,
        array $requestLines,
        bool $isComplete = true
    ): array;

    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, SalesChannelContext $salesChannelContext): array;
}
