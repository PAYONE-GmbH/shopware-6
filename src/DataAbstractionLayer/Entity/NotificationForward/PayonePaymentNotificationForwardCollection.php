<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationForward;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                        add(PayonePaymentNotificationForwardEntity $entity)
 * @method void                                        set(string $key, PayonePaymentNotificationForwardEntity $entity)
 * @method PayonePaymentNotificationForwardEntity[]    getIterator()
 * @method PayonePaymentNotificationForwardEntity[]    getElements()
 * @method PayonePaymentNotificationForwardEntity|null get(string $key)
 * @method PayonePaymentNotificationForwardEntity|null first()
 * @method PayonePaymentNotificationForwardEntity|null last()
 */
class PayonePaymentNotificationForwardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentNotificationForwardEntity::class;
    }
}
