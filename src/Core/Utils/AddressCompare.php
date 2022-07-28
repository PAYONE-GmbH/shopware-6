<?php

declare(strict_types=1);

namespace PayonePayment\Core\Utils;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class AddressCompare
{
    public static function areOrderAddressesIdentical(OrderAddressEntity $entity1, OrderAddressEntity $entity2): bool
    {
        $fieldsToCompare = [
            'firstName',
            'lastName',
            'salutationId',
            'company',
            'street',
            'additionalAddressLine1',
            'additionalAddressLine2',
            'zipcode',
            'city',
            'countryId',
            'countryStateId',
        ];

        return self::areEntitiesIdentical($entity1, $entity2, $fieldsToCompare);
    }

    public static function areCustomerAddressesIdentical(CustomerAddressEntity $entity1, CustomerAddressEntity $entity2): bool
    {
        $fieldsToCompare = [
            'firstName',
            'lastName',
            'salutationId',
            'company',
            'street',
            'additionalAddressLine1',
            'additionalAddressLine2',
            'zipcode',
            'city',
            'countryId',
            'countryStateId',
        ];

        return self::areEntitiesIdentical($entity1, $entity2, $fieldsToCompare);
    }

    private static function areEntitiesIdentical(Entity $entity1, Entity $entity2, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($entity1->get($field) !== $entity2->get($field)) {
                return false;
            }
        }

        return true;
    }
}
