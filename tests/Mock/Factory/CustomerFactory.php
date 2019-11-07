<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Test\Constants;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Defaults;

class CustomerFactory
{
    public static function getCustomer(): CustomerEntity
    {
        $customer = new CustomerEntity();

        $customer->setLanguageId(Defaults::LANGUAGE_SYSTEM);
        $customer->setFirstName('');
        $customer->setLastName('');
        $customer->setEmail('');

        $address = new CustomerAddressEntity();
        $address->setSalutationId(Constants::SALUTATION_ID);
        $address->setFirstName('');
        $address->setLastName('');
        $address->setStreet('');
        $address->setZipcode('');
        $address->setCity('');
        $address->setCountryId(Constants::COUNTRY_ID);

        $customer->setActiveBillingAddress($address);
        $customer->setActiveShippingAddress($address);

        return $customer;
    }
}
