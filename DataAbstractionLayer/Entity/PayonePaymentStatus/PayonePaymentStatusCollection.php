<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentStatus;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class PayonePaymentStatusCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentStatusEntity::class;
    }
}
