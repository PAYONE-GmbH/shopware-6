<?php

declare(strict_types=1);

namespace PayonePayment\DataHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

readonly class WebhookLogDataHandler
{
    public function __construct(
        protected EntityRepository $webhookLogRepository,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $webhookData
     */
    public function createWebhookLog(
        OrderEntity $order,
        array $webhookData,
        Context $context,
    ): void {
        $webhookLog = [
            'orderId'          => $order->getId(),
            'transactionId'    => $webhookData['txid'],
            'transactionState' => $webhookData['txaction'],
            'sequenceNumber'   => (int) $webhookData['sequencenumber'],
            'clearingType'     => $webhookData['clearingtype'],
            'webhookDetails'   => $webhookData,
            'webhookDateTime'  => new \DateTime(),
        ];

        try {
            $this->webhookLogRepository->create([$webhookLog], $context);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to create webhook log', [
                'message' => $exception->getMessage(),
                'data'    => $webhookLog,
                'trace'   => $exception->getTraceAsString(),
            ]);
        }
    }
}
