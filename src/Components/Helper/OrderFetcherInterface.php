<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface OrderFetcherInterface
{
    public function getOrderById(string $orderId, Context $context): ?OrderEntity;
}
