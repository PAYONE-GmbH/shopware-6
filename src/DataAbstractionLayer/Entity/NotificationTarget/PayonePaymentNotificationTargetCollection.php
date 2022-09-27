<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationTarget;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                       add(PayonePaymentNotificationTargetEntity $entity)
 * @method void                                       set(string $key, PayonePaymentNotificationTargetEntity $entity)
 * @method PayonePaymentNotificationTargetEntity[]    getIterator()
 * @method PayonePaymentNotificationTargetEntity[]    getElements()
 * @method PayonePaymentNotificationTargetEntity|null get(string $key)
 * @method PayonePaymentNotificationTargetEntity|null first()
 * @method PayonePaymentNotificationTargetEntity|null last()
 */
class PayonePaymentNotificationTargetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationTargetEntity::class;
    }
}
