<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\OrderActionLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentOrderActionLogEntity>
 */
class PayonePaymentOrderActionLogCollection extends EntityCollection
{
    #[\Override]
    protected function getExpectedClass(): string
    {
        return PayonePaymentOrderActionLogEntity::class;
    }
}
