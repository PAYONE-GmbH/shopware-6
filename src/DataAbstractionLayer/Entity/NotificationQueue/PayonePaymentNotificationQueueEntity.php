<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationQueue;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentNotificationQueueEntity extends Entity
{
    use EntityIdTrait;

    protected string $notificationTargetId;

    protected string $status;

    protected ?int $responseHttpCode;

    protected ?string $message;

    protected \DateTimeInterface $lastExecutionTime;

    protected \DateTimeInterface $nextExecutionTime;

    public function getNotificationTargetId(): string
    {
        return $this->notificationTargetId;
    }

    public function setNotificationTargetId(string $notificationTargetId): void
    {
        $this->notificationTargetId = $notificationTargetId;
    }

    public function getResponseHttpCode(): ?int
    {
        return $this->responseHttpCode;
    }

    public function setResponseHttpCode(?int $responseHttpCode): void
    {
        $this->responseHttpCode = $responseHttpCode;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getLastExecutionTime(): ?\DateTimeInterface
    {
        return $this->lastExecutionTime;
    }

    public function setLastExecutionTime(?\DateTimeInterface $lastExecutionTime): void
    {
        $this->lastExecutionTime = $lastExecutionTime;
    }

    public function setNextExecutionTime(\DateTimeInterface $nextExecutionTime): void
    {
        $this->nextExecutionTime = $nextExecutionTime;
    }

    public function getNextExecutionTime(): \DateTimeInterface
    {
        return $this->nextExecutionTime;
    }
}
