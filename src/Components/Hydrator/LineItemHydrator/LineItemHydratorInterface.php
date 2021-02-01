<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\System\Currency\CurrencyEntity;

interface LineItemHydratorInterface
{
    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderLineItemCollection $orderLineItems,
        array $requestLines
    ): array;

    public function mapOrderLines(CurrencyEntity $currency, OrderLineItemCollection $lineItemCollection): array;
}
