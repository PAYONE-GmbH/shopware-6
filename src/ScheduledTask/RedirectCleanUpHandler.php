<?php

declare(strict_types=1);

namespace PayonePayment\ScheduledTask;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class RedirectCleanUpHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly RedirectHandler $redirectHandler,
        private readonly LoggerInterface $logger,
        LoggerInterface $exceptionLogger,
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public static function getHandledMessages(): iterable
    {
        return [ RedirectCleanUp::class ];
    }

    #[\Override]
    public function run(): void
    {
        $this->logger->debug('Starting to clean up PAYONE Redirects...');

        $affectedRows = $this->redirectHandler->cleanup();

        $this->logger->debug(\sprintf('Finished cleaning up %d PAYONE Redirects.', $affectedRows));
    }
}
