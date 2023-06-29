<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\Command;

use Shopware\Core\Framework\Context;

class NotificationForwardCommand
{
    private Context $context;

    public function __construct(private array $notificationTargetIds, Context $context)
    {
        $this->context = $context;
    }

    public function getNotificationTargetIds(): array
    {
        return $this->notificationTargetIds;
    }

    public function setNotificationTargetIds(array $notificationTargetIds): void
    {
        $this->notificationTargetIds = $notificationTargetIds;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }
}
