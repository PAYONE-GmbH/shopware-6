<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\LineItem;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

interface LineItemDataHandlerInterface
{
    public function saveLineItemData(OrderLineItemEntity $lineItem, Context $context, array $data): void;

    public function saveLineItemDataById(string $lineItemId, Context $context, array $data): void;
}
