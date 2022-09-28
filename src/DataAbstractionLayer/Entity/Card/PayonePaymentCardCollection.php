<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(PayonePaymentCardEntity $entity)
 * @method void                         set(string $key, PayonePaymentCardEntity $entity)
 * @method PayonePaymentCardEntity[]    getIterator()
 * @method PayonePaymentCardEntity[]    getElements()
 * @method PayonePaymentCardEntity|null get(string $key)
 * @method PayonePaymentCardEntity|null first()
 * @method PayonePaymentCardEntity|null last()
 */
class PayonePaymentCardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentCardEntity::class;
    }
}
