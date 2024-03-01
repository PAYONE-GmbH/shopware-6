<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationQueue;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentNotificationQueueEntity>
 */
class PayonePaymentNotificationQueueCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationQueueEntity::class;
    }
}
