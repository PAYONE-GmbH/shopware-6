<?php

declare(strict_types=1);

namespace PayonePayment\ScheduledTask;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class RedirectCleanUpHandler extends ScheduledTaskHandler
{
    /** @var RedirectHandler */
    private $redirectHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        RedirectHandler $redirectHandler,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);

        $this->redirectHandler = $redirectHandler;
        $this->logger          = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [RedirectCleanUp::class];
    }

    public function run(): void
    {
        $this->logger->debug('Starting to clean up PAYONE Redirects...');

        $affectedRows = $this->redirectHandler->cleanup();

        $this->logger->debug(sprintf('Finished cleaning up %d PAYONE Redirects.', $affectedRows));
    }
}
