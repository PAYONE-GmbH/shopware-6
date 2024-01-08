<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\WebhookLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentWebhookLogEntity extends Entity
{
    use EntityIdTrait;

    protected ?OrderEntity $order = null;

    protected string $orderId;

    protected string $transactionId;

    protected string $transactionState;

    protected int $sequenceNumber;

    protected string $clearingType;

    protected array $webhookDetails;

    protected \DateTimeInterface $webhookDateTime;

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getTransactionState(): string
    {
        return $this->transactionState;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function getClearingType(): string
    {
        return $this->clearingType;
    }

    public function getWebhookDetails(): array
    {
        return $this->webhookDetails;
    }

    public function getWebhookDateTime(): \DateTimeInterface
    {
        return $this->webhookDateTime;
    }
}
