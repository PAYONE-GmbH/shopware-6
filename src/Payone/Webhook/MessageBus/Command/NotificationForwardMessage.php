<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\Command;

use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class NotificationForwardMessage implements AsyncMessageInterface
{
    public function __construct(
        private readonly string $notificationTargetId,
        private readonly array $requestData,
        private readonly string $paymentTransactionId,
        private readonly string $clientIp,
        private int $attempt = 1,
    ) {
    }

    public function getNotificationTargetId(): string
    {
        return $this->notificationTargetId;
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function getPaymentTransactionId(): string
    {
        return $this->paymentTransactionId;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function setAttempt(int $attempt): void
    {
        $this->attempt = $attempt;
    }
}
