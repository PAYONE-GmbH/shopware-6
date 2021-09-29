<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\Command;

use Shopware\Core\Framework\Context;

class NotificationForwardCommand
{
    /** @var array */
    private $notificationTargetIds;

    /** @var Context */
    private $context;

    public function __construct(array $notificationTargetIds, Context $context)
    {
        $this->notificationTargetIds = $notificationTargetIds;
        $this->context               = $context;
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
