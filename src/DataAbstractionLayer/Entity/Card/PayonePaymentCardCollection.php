<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentCardEntity>
 */
class PayonePaymentCardCollection extends EntityCollection
{
    #[\Override]
    protected function getExpectedClass(): string
    {
        return PayonePaymentCardEntity::class;
    }
}
