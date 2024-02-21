<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\WebhookLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface WebhookLogDataHandlerInterface
{
    /**
     * @param array<string, mixed> $webhookData
     */
    public function createWebhookLog(
        OrderEntity $order,
        array $webhookData,
        Context $context
    ): void;
}
