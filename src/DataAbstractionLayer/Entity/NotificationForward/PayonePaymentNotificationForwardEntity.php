<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationForward;

use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentNotificationForwardEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $notificationTargetId;

    /** @var null|PayonePaymentNotificationTargetEntity */
    protected $notificationTarget;

    /** @var string */
    protected $ip;

    /** @var string */
    protected $txaction;

    /** @var string */
    protected $response;

    /** @var string */
    protected $transactionId;

    /** @var OrderTransactionEntity */
    protected $tranaction;

    /** @var string */
    protected $content;

    public function getNotificationTargetId(): string
    {
        return $this->notificationTargetId;
    }

    public function setNotificationTargetId(string $notificationTargetId): void
    {
        $this->notificationTargetId = $notificationTargetId;
    }

    public function getNotificationTarget(): ?PayonePaymentNotificationTargetEntity
    {
        return $this->notificationTarget;
    }

    public function setNotificationTarget(?PayonePaymentNotificationTargetEntity $notificationTarget): void
    {
        $this->notificationTarget = $notificationTarget;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getTxaction(): string
    {
        return $this->txaction;
    }

    public function setTxaction(string $txaction): void
    {
        $this->txaction = $txaction;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTranaction(): OrderTransactionEntity
    {
        return $this->tranaction;
    }

    public function setTranaction(OrderTransactionEntity $tranaction): void
    {
        $this->tranaction = $tranaction;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
