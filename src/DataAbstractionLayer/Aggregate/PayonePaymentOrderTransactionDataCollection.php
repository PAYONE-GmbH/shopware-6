<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                         add(PayonePaymentOrderTransactionDataEntity $entity)
 * @method void                                         set(string $key, PayonePaymentOrderTransactionDataEntity $entity)
 * @method PayonePaymentOrderTransactionDataEntity[]    getIterator()
 * @method PayonePaymentOrderTransactionDataEntity[]    getElements()
 * @method null|PayonePaymentOrderTransactionDataEntity get(string $key)
 * @method null|PayonePaymentOrderTransactionDataEntity first()
 * @method null|PayonePaymentOrderTransactionDataEntity last()
 */
class PayonePaymentOrderTransactionDataCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return PayonePaymentOrderTransactionDataEntity::class;
    }
}
