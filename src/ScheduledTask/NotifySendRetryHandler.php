<?php

declare(strict_types=1);

namespace PayonePayment\ScheduledTask;

use PayonePayment\Components\ResendNotifyHandler\ResendNotifyHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class NotifySendRetryHandler extends ScheduledTaskHandler
{

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly ResendNotifyHandler $resendNotifyHandler
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [NotifySendRetry::class];
    }

    public function run(): void
    {
        $this->resendNotifyHandler->send();
    }
}
