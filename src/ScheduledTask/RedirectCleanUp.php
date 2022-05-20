<?php

declare(strict_types=1);

namespace PayonePayment\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class RedirectCleanUp extends ScheduledTask
{
    private const TIME_INTERVAL_DAILY = 86400;

    public static function getTaskName(): string
    {
        return 'payone_payment.redirect_clean_up';
    }

    public static function getDefaultInterval(): int
    {
        return self::TIME_INTERVAL_DAILY;
    }
}
