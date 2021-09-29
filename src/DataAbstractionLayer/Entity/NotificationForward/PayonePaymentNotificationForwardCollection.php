<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationForward;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                        add(PayonePaymentNotificationForwardEntity $entity)
 * @method void                                        set(string $key, PayonePaymentNotificationForwardEntity $entity)
 * @method PayonePaymentNotificationForwardEntity[]    getIterator()
 * @method PayonePaymentNotificationForwardEntity[]    getElements()
 * @method null|PayonePaymentNotificationForwardEntity get(string $key)
 * @method null|PayonePaymentNotificationForwardEntity first()
 * @method null|PayonePaymentNotificationForwardEntity last()
 */
class PayonePaymentNotificationForwardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationForwardEntity::class;
    }
}
