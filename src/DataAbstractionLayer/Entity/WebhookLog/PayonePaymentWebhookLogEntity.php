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

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTransactionState(): string
    {
        return $this->transactionState;
    }

    public function setTransactionState(string $transactionState): void
    {
        $this->transactionState = $transactionState;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(int $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }

    public function getClearingType(): string
    {
        return $this->clearingType;
    }

    public function setClearingType(string $clearingType): void
    {
        $this->clearingType = $clearingType;
    }

    public function getWebhookDetails(): array
    {
        return $this->webhookDetails;
    }

    public function setWebhookDetails(array $webhookDetails): void
    {
        $this->webhookDetails = $webhookDetails;
    }

    public function getWebhookDateTime(): \DateTimeInterface
    {
        return $this->webhookDateTime;
    }

    public function setWebhookDateTime(\DateTimeInterface $webhookDateTime): void
    {
        $this->webhookDateTime = $webhookDateTime;
    }
}
