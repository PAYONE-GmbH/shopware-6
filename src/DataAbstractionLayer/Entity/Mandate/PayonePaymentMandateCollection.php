<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Mandate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentMandateEntity>
 */
class PayonePaymentMandateCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentMandateEntity::class;
    }
}
