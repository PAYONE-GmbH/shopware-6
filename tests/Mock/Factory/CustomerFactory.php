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
        $customer->setFirstName('First');
        $customer->setLastName('Last');
        $customer->setEmail('first.last@example.com');

        $address = new CustomerAddressEntity();
        $address->setSalutationId(Constants::SALUTATION_ID);
        $address->setFirstName('First');
        $address->setLastName('Last');
        $address->setStreet('Some Street 1');
        $address->setZipcode('12345');
        $address->setCity('Some City');
        $address->setCountryId(Constants::COUNTRY_ID);

        $customer->setActiveBillingAddress($address);
        $customer->setActiveShippingAddress($address);

        return $customer;
    }
}
