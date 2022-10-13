<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Mandate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(PayonePaymentMandateEntity $entity)
 * @method void                            set(string $key, PayonePaymentMandateEntity $entity)
 * @method PayonePaymentMandateEntity[]    getIterator()
 * @method PayonePaymentMandateEntity[]    getElements()
 * @method PayonePaymentMandateEntity|null get(string $key)
 * @method PayonePaymentMandateEntity|null first()
 * @method PayonePaymentMandateEntity|null last()
 */
class PayonePaymentMandateCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentMandateEntity::class;
    }
}
