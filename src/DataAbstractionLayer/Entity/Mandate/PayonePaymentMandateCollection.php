<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Mandate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(PayonePaymentMandateEntity $entity)
 * @method void                            set(string $key, PayonePaymentMandateEntity $entity)
 * @method PayonePaymentMandateEntity[]    getIterator()
 * @method PayonePaymentMandateEntity[]    getElements()
 * @method null|PayonePaymentMandateEntity get(string $key)
 * @method null|PayonePaymentMandateEntity first()
 * @method null|PayonePaymentMandateEntity last()
 */
class PayonePaymentMandateCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentMandateEntity::class;
    }
}
