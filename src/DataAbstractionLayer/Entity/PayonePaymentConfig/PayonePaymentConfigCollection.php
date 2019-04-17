<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class PayonePaymentConfigCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayonePaymentConfigEntity::class;
    }
}
