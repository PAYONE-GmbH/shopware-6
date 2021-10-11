<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationTarget;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                       add(PayonePaymentNotificationTargetEntity $entity)
 * @method void                                       set(string $key, PayonePaymentNotificationTargetEntity $entity)
 * @method PayonePaymentNotificationTargetEntity[]    getIterator()
 * @method PayonePaymentNotificationTargetEntity[]    getElements()
 * @method null|PayonePaymentNotificationTargetEntity get(string $key)
 * @method null|PayonePaymentNotificationTargetEntity first()
 * @method null|PayonePaymentNotificationTargetEntity last()
 */
class PayonePaymentNotificationTargetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationTargetEntity::class;
    }
}
