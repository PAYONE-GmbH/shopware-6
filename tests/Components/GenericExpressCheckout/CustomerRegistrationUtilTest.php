<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \PayonePayment\Components\GenericExpressCheckout\CustomerRegistrationUtil
 */
class CustomerRegistrationUtilTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfAllRequiredKeysArePresentAndHaveValidValues(): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'shipping_firstname' => 'Max',
                'shipping_lastname' => 'Mustermann',
                'shipping_company' => 'my-company',
                'shipping_street' => 'pay-street 123',
                'shipping_addressaddition' => 'c/o Petra',
                'shipping_zip' => '65432',
                'shipping_city' => 'Berlin',
                'shipping_state' => 'Ohio',
                'shipping_country' => 'DE',
                'telephonenumber' => '0123456789',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('guest', $data);
        static::assertTrue($data['guest'], 'guest should be always `true`, because the customer did not select if he wan\'t to be a customer');
        static::assertArrayHasKey('email', $data);
        static::assertEquals('test@localhost.local', $data['email']);
        static::assertArrayHasKey('firstName', $data);
        static::assertEquals('Max', $data['firstName']);
        static::assertArrayHasKey('lastName', $data);
        static::assertEquals('Mustermann', $data['lastName']);
        static::assertArrayHasKey('acceptedDataProtection', $data);
        static::assertTrue($data['acceptedDataProtection'], '`acceptedDataProtection` should be always `true`.');
        static::assertArrayHasKey('billingAddress', $data); // values of it will be tested in other tests
        static::assertArrayHasKey('salutationId', $data['billingAddress']);
        static::assertArrayHasKey('firstName', $data['billingAddress']);
        static::assertArrayHasKey('lastName', $data['billingAddress']);
        static::assertArrayHasKey('street', $data['billingAddress']);
        static::assertArrayHasKey('additionalAddressLine1', $data['billingAddress']);
        static::assertArrayHasKey('zipcode', $data['billingAddress']);
        static::assertArrayHasKey('city', $data['billingAddress']);
        static::assertArrayHasKey('countryId', $data['billingAddress']);
        static::assertArrayHasKey('phone', $data['billingAddress']);
    }

    public function testWithOnlyShippingAddress(): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',
                'shipping_firstname' => 'Max',
                'shipping_lastname' => 'Mustermann',
                'shipping_company' => 'my-company',
                'shipping_street' => 'pay-street 123',
                'shipping_addressaddition' => 'c/o Petra',
                'shipping_zip' => '65432',
                'shipping_city' => 'Berlin',
                'shipping_state' => 'Ohio',
                'shipping_country' => 'DE',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it would be the same as billing address');
        static::assertEquals('Max', $data['billingAddress']['firstName']);
        static::assertEquals('Mustermann', $data['billingAddress']['lastName']);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals('pay-street 123', $data['billingAddress']['street']);
        static::assertEquals('c/o Petra', $data['billingAddress']['additionalAddressLine1']);
        static::assertEquals('65432', $data['billingAddress']['zipcode']);
        static::assertEquals('Berlin', $data['billingAddress']['city']);

        static::assertTrue(Uuid::isValid($data['billingAddress']['countryId']), $data['billingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['billingAddress']['phone']);
    }

    public function testWithNoSpecificAddress(): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'company' => 'my-company',
                'street' => 'pay-street 123',
                'addressaddition' => 'c/o Petra',
                'zip' => '65432',
                'city' => 'Berlin',
                'state' => 'Ohio',
                'country' => 'DE',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it would be the same as billing address');
        static::assertEquals('Max', $data['billingAddress']['firstName']);
        static::assertEquals('Mustermann', $data['billingAddress']['lastName']);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals('pay-street 123', $data['billingAddress']['street']);
        static::assertEquals('c/o Petra', $data['billingAddress']['additionalAddressLine1']);
        static::assertEquals('65432', $data['billingAddress']['zipcode']);
        static::assertEquals('Berlin', $data['billingAddress']['city']);

        static::assertTrue(Uuid::isValid($data['billingAddress']['countryId']), $data['billingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['billingAddress']['phone']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     */
    public function testWithBillingAddress(string $billingPrefix): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it would be the same as billing address');
        static::assertEquals('Max', $data['billingAddress']['firstName']);
        static::assertEquals('Mustermann', $data['billingAddress']['lastName']);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals('pay-street 123', $data['billingAddress']['street']);
        static::assertEquals('c/o Petra', $data['billingAddress']['additionalAddressLine1']);
        static::assertEquals('65432', $data['billingAddress']['zipcode']);
        static::assertEquals('Berlin', $data['billingAddress']['city']);

        static::assertTrue(Uuid::isValid($data['billingAddress']['countryId']), $data['billingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['billingAddress']['phone']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     */
    public function testWithBillingAndShippingAddress(string $billingPrefix): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',

                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                'shipping_firstname' => 'Petra',
                'shipping_lastname' => 'Musterfrau',
                'shipping_company' => 'your-company',
                'shipping_street' => 'refund-street 123',
                'shipping_addressaddition' => 'c/o Max',
                'shipping_zip' => '98765',
                'shipping_city' => 'Köln',
                'shipping_state' => 'New York',
                'shipping_country' => 'AT',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayHasKey('shippingAddress', $data);
        static::assertEquals('Max', $data['billingAddress']['firstName']);
        static::assertEquals('Mustermann', $data['billingAddress']['lastName']);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals('pay-street 123', $data['billingAddress']['street']);
        static::assertEquals('c/o Petra', $data['billingAddress']['additionalAddressLine1']);
        static::assertEquals('65432', $data['billingAddress']['zipcode']);
        static::assertEquals('Berlin', $data['billingAddress']['city']);
        static::assertTrue(Uuid::isValid($data['billingAddress']['countryId']), $data['billingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['billingAddress']['phone']);

        static::assertEquals('Petra', $data['shippingAddress']['firstName']);
        static::assertEquals('Musterfrau', $data['shippingAddress']['lastName']);
        static::assertEquals('your-company', $data['shippingAddress']['company']);
        static::assertEquals('refund-street 123', $data['shippingAddress']['street']);
        static::assertEquals('c/o Max', $data['shippingAddress']['additionalAddressLine1']);
        static::assertEquals('98765', $data['shippingAddress']['zipcode']);
        static::assertEquals('Köln', $data['shippingAddress']['city']);
        static::assertTrue(Uuid::isValid($data['shippingAddress']['countryId']), $data['shippingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['shippingAddress']['phone']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     */
    public function testWithBillingCompany(string $billingPrefix): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',

                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals(CustomerEntity::ACCOUNT_TYPE_BUSINESS, $data['accountType']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     */
    public function testWithoutBillingCompany(string $billingPrefix): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',

                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertNull($data['billingAddress']['company'] ?? null);
        static::assertEquals(CustomerEntity::ACCOUNT_TYPE_PRIVATE, $data['accountType']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     */
    public function testWithShippingCompany(string $billingPrefix): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',

                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                'shipping_firstname' => 'Petra',
                'shipping_lastname' => 'Musterfrau',
                'shipping_company' => 'your-company',
                'shipping_street' => 'refund-street 123',
                'shipping_addressaddition' => 'c/o Max',
                'shipping_zip' => '98765',
                'shipping_city' => 'Köln',
                'shipping_state' => 'New York',
                'shipping_country' => 'AT',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertNull($data['billingAddress']['company'] ?? null, 'billing-company should be null and the the fallback to the shipping-company should not used.');
        static::assertEquals(CustomerEntity::ACCOUNT_TYPE_PRIVATE, $data['accountType']);

        static::assertArrayHasKey('shippingAddress', $data);
        static::assertEquals('your-company', $data['shippingAddress']['company']);
    }

    public function testIfPayPalFirstnameGotCorrectlyExtracted(): void
    {
        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                'telephonenumber' => '0123456789',

                'lastname' => 'Petra Musterfrau',
                'street' => 'pay-street 123',
                'addressaddition' => 'c/o Petra',
                'zip' => '65432',
                'city' => 'Berlin',
                'state' => 'Ohio',
                'country' => 'DE',

                'shipping_firstname' => 'Petra',
                'shipping_lastname' => 'Musterfrau',
                'shipping_company' => 'your-company',
                'shipping_street' => 'refund-street 123',
                'shipping_addressaddition' => 'c/o Max',
                'shipping_zip' => '98765',
                'shipping_city' => 'Köln',
                'shipping_state' => 'New York',
                'shipping_country' => 'AT',
            ],
        ], Context::createDefaultContext())->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayHasKey('firstName', $data);
        static::assertArrayHasKey('lastName', $data);
        static::assertEquals('Petra', $data['firstName']);
        static::assertEquals('Musterfrau', $data['lastName']);

        static::assertArrayHasKey('firstName', $data['billingAddress']);
        static::assertArrayHasKey('lastName', $data['billingAddress']);
        static::assertEquals('Petra', $data['billingAddress']['firstName']);
        static::assertEquals('Musterfrau', $data['billingAddress']['lastName']);
    }

    protected static function billingAddressDataProvider(): array
    {
        return [
            [''],
            ['billing_'],
        ];
    }
}
