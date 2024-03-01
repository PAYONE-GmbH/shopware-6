<?php

declare(strict_types=1);

namespace PayonePayment\Components\ResendNotifyHandler;

use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use PayonePayment\DataAbstractionLayer\Entity\NotificationQueue\PayonePaymentNotificationQueueEntity;
use PayonePayment\Payone\Webhook\MessageBus\MessageHandler\NotificationForwardHandler;

class ResendNotifyHandler
{
    public function __construct(
        private readonly EntityRepository $notificationQueueRepository,
        private readonly NotificationForwardHandler $notificationForwardHandler
    ) {
    }

    public function send(): void
    {
        $criteria = new Criteria();
        $currentDate = new \DateTime();
        $criteria->addFilter(new RangeFilter('nextExecutionTime', [
           RangeFilter::LTE => $currentDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]));

        $notificationQueue = $this->notificationQueueRepository->search($criteria, Context::createDefaultContext())->getEntities();

        /** @var PayonePaymentNotificationQueueEntity $notification */
        foreach ($notificationQueue as $notification) {
            /** @var NotificationForwardCommand $message */
            $message = unserialize(base64_decode($notification->getMessage()),[]);
            $this->notificationForwardHandler->handle($message, true);
            $this->notificationQueueRepository->delete([['id' => $notification->getId()]], Context::createDefaultContext());
        }
        echo "current time: " . $currentDate->format(Defaults::STORAGE_DATE_TIME_FORMAT) . "\n";
    }
}
