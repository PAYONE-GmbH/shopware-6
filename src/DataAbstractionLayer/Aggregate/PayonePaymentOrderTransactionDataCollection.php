<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentOrderTransactionDataEntity>
 */
class PayonePaymentOrderTransactionDataCollection extends EntityCollection
{
    #[\Override]
    public function getExpectedClass(): string
    {
        return PayonePaymentOrderTransactionDataEntity::class;
    }
}
