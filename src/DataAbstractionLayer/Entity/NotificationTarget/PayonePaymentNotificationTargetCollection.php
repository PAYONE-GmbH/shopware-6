<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationTarget;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentNotificationTargetEntity>
 */
class PayonePaymentNotificationTargetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationTargetEntity::class;
    }
}
