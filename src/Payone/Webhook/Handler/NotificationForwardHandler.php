<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class NotificationForwardHandler implements WebhookHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $notificationTargetRepository;

    /** @var EntityRepositoryInterface */
    private $notificationForwardRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityRepositoryInterface $notificationTargetRepository, EntityRepositoryInterface $notificationForwardRepository, LoggerInterface $logger)
    {
        $this->notificationTargetRepository  = $notificationTargetRepository;
        $this->notificationForwardRepository = $notificationForwardRepository;
        $this->logger                        = $logger;
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SalesChannelContext $salesChannelContext, array $data): void
    {
        //TODO: get notification targets based on txactions
        //TODO: store notification forward
        //TODO: queue notification forward ids

        //TODO: add field for message -> serialized TEXT, ISO encoding
        //TODO: add field for transactions

        //TODO: message bus handler
    }
}
