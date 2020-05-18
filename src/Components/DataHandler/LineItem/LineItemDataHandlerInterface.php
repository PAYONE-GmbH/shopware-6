<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\LineItem;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;

interface LineItemDataHandlerInterface
{
    public function saveLineItemData(array $lineItemData, Context $context): void;
}
