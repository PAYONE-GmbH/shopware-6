<?php

declare(strict_types=1);

namespace PayonePayment\Core\Utils;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class AddressCompare
{
    private const ADDRESS_FIELDS = [
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

    public static function areOrderAddressesIdentical(OrderAddressEntity $entity1, OrderAddressEntity $entity2): bool
    {
        return self::areEntitiesIdentical($entity1, $entity2, self::ADDRESS_FIELDS);
    }

    public static function areCustomerAddressesIdentical(CustomerAddressEntity $entity1, CustomerAddressEntity $entity2): bool
    {
        return self::areEntitiesIdentical($entity1, $entity2, self::ADDRESS_FIELDS);
    }

    public static function areRawAddressesIdentical(array $address1, array $address2): bool
    {
        return self::areArraysIdentical($address1, $address2, self::ADDRESS_FIELDS);
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

    private static function areArraysIdentical(array $array1, array $array2, array $fields): bool
    {
        foreach ($fields as $field) {
            if (($array1[$field] ?? null) !== ($array2[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
