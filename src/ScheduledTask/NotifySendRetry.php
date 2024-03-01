<?php

declare(strict_types=1);

namespace PayonePayment\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class NotifySendRetry extends ScheduledTask
{
    private const TIME_INTERVAL_DAILY = 300;

    public static function getTaskName(): string
    {
        return 'payone_payment.notify_send_retry';
    }

    public static function getDefaultInterval(): int
    {
        return self::TIME_INTERVAL_DAILY;
    }
}
