<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\OrderActionLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface OrderActionLogDataHandlerInterface
{
    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $response
     */
    public function createOrderActionLog(
        OrderEntity $order,
        array $request,
        array $response,
        Context $context
    ): void;
}
