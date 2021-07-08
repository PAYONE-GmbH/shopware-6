<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationForward;

use DateTimeInterface;
use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentNotificationForwardEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $notificationTargetId;

    /** @var PayonePaymentNotificationTargetEntity */
    protected $notificationTarget;

    /** @var string */
    protected $txaction;

    /** @var string */
    protected $response;

    public function getNotificationTargetId(): string
    {
        return $this->notificationTargetId;
    }

    public function setNotificationTargetId(string $notificationTargetId): void
    {
        $this->notificationTargetId = $notificationTargetId;
    }

    public function getNotificationTarget(): PayonePaymentNotificationTargetEntity
    {
        return $this->notificationTarget;
    }

    public function setNotificationTarget(PayonePaymentNotificationTargetEntity $notificationTarget): void
    {
        $this->notificationTarget = $notificationTarget;
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
}
