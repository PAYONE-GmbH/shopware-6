<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class AddressCompareService
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

    public function areOrderAddressesIdentical(
        OrderAddressEntity $entity1,
        OrderAddressEntity $entity2,
    ): bool {
        return $this->areEntitiesIdentical($entity1, $entity2);
    }

    public function areCustomerAddressesIdentical(
        CustomerAddressEntity $entity1,
        CustomerAddressEntity $entity2,
    ): bool {
        return $this->areEntitiesIdentical($entity1, $entity2);
    }

    public function areRawAddressesIdentical(array $address1, array $address2): bool
    {
        return $this->areArraysIdentical($address1, $address2);
    }

    private function areEntitiesIdentical(Entity $entity1, Entity $entity2): bool
    {
        foreach (self::ADDRESS_FIELDS as $field) {
            if ($entity1->get($field) !== $entity2->get($field)) {
                return false;
            }
        }

        return true;
    }

    private function areArraysIdentical(array $array1, array $array2): bool
    {
        foreach (self::ADDRESS_FIELDS as $field) {
            if (($array1[$field] ?? null) !== ($array2[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
