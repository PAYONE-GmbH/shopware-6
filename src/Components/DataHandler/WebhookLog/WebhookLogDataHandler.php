<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\WebhookLog;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class WebhookLogDataHandler implements WebhookLogDataHandlerInterface
{
    public function __construct(
        protected readonly EntityRepository $webhookLogRepository,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $webhookData
     */
    public function createWebhookLog(
        OrderEntity $order,
        array $webhookData,
        Context $context
    ): void {
        $webhookLog = [
            'orderId' => $order->getId(),
            'transactionId' => $webhookData['txid'],
            'transactionState' => $webhookData['txaction'],
            'sequenceNumber' => (int) $webhookData['sequencenumber'],
            'clearingType' => $webhookData['clearingtype'],
            'webhookDetails' => $webhookData,
            'webhookDateTime' => new \DateTime(),
        ];

        try {
            $this->webhookLogRepository->create([$webhookLog], $context);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to create webhook log', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
