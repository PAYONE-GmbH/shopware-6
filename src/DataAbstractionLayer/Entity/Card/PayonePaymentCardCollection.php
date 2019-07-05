<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void             add(SocialEntity $entity)
 * @method void             set(string $key, SocialEntity $entity)
 * @method SocialEntity[]    getIterator()
 * @method SocialEntity[]    getElements()
 * @method null|SocialEntity get(string $key)
 * @method null|SocialEntity first()
 * @method null|SocialEntity last()
 */
class PayonePaymentCardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SocialEntity::class;
    }
}
