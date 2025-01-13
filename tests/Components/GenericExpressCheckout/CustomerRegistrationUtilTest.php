<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \PayonePayment\Components\GenericExpressCheckout\CustomerRegistrationUtil
 */
class CustomerRegistrationUtilTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsCorrectDataWhenBillingDataIsMissingInResponse(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

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
                'shipping_telephonenumber' => '0123456789',
            ],
        ], $salesChannelContext)->all();

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
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it would be the same as billing address');
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

    /**
     * @dataProvider billingAddressDataProvider
     * @testdox It returns correct data when shipping data is missing in response and billing data is available with prefix "$billingPrefix"
     */
    public function testItReturnsCorrectDataWhenShippingDataIsMissingInResponse(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',
                $billingPrefix . 'telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

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
     * @testdox It returns correct data when different shipping and billing (with prefix "$billingPrefix") are available in response
     */
    public function testItReturnsCorrectDataWhenDifferentShippingAndBillingAreAvailableInResponse(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                $billingPrefix . 'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                'shipping_telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

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
     * @testdox It returns correct data when same shipping and billing (with prefix "$billingPrefix") are available in response
     */
    public function testItReturnsCorrectDataWhenSameShippingAndBillingAreAvailableInResponse(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                $billingPrefix . 'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                'shipping_telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

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
     * @testdox It returns correct data when billing company (with prefix "$billingPrefix") is available in response
     */
    public function testItReturnsCorrectDataWhenBillingCompanyIsAvailableInResponse(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                $billingPrefix . 'telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertEquals('my-company', $data['billingAddress']['company']);
        static::assertEquals(CustomerEntity::ACCOUNT_TYPE_BUSINESS, $data['accountType']);
    }

    /**
     * @dataProvider billingAddressDataProvider
     * @testdox It returns correct data when no billing company (with prefix "$billingPrefix") is available in response
     */
    public function testItReturnsCorrectDataWhenNoBillingCompanyIsAvailableInResponse(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                $billingPrefix . 'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'street' => 'pay-street 123',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'city' => 'Berlin',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',
            ],
        ], $salesChannelContext)->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertNull($data['billingAddress']['company'] ?? null);
        static::assertEquals(CustomerEntity::ACCOUNT_TYPE_PRIVATE, $data['accountType']);
    }

    public function testItReturnsCorrectNameDataForPayPalV1(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

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

                'shipping_telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

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

    /**
     * @dataProvider billingAddressDataProvider
     * @testdox It throws exception if billing (with prefix "$billingPrefix") and shipping address are incomplete
     */
    public function testItThrowsExceptionIfBillingAndShippingAddressAreIncomplete(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $this->expectException(\RuntimeException::class);

        $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                // Street and city missing
                $billingPrefix . 'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                // Firstname and lastname missing
                'shipping_telephonenumber' => '0123456789',
                'shipping_company' => 'my-company',
                'shipping_street' => 'pay-street 123',
                'shipping_addressaddition' => 'c/o Petra',
                'shipping_zip' => '65432',
                'shipping_city' => 'Berlin',
                'shipping_state' => 'Ohio',
                'shipping_country' => 'DE',
            ],
        ], $salesChannelContext)->all();
    }

    /**
     * @dataProvider billingAddressDataProvider
     * @testdox It takes complete shipping address as billing address if billing address (with prefix "$billingPrefix") is incomplete
     */
    public function testItTakesCompleteShippingAddressAsBillingAddressIfBillingAddressIsIncomplete(string $billingPrefix): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                // Street and city missing
                $billingPrefix . 'telephonenumber' => '0123456789',
                $billingPrefix . 'firstname' => 'Max',
                $billingPrefix . 'lastname' => 'Mustermann',
                $billingPrefix . 'company' => 'my-company',
                $billingPrefix . 'addressaddition' => 'c/o Petra',
                $billingPrefix . 'zip' => '65432',
                $billingPrefix . 'state' => 'Ohio',
                $billingPrefix . 'country' => 'DE',

                // Complete
                'shipping_telephonenumber' => '0123456789',
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
        ], $salesChannelContext)->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it would be the same as billing address');
        static::assertEquals('Petra', $data['billingAddress']['firstName']);
        static::assertEquals('Musterfrau', $data['billingAddress']['lastName']);
        static::assertEquals('your-company', $data['billingAddress']['company']);
        static::assertEquals('refund-street 123', $data['billingAddress']['street']);
        static::assertEquals('c/o Max', $data['billingAddress']['additionalAddressLine1']);
        static::assertEquals('98765', $data['billingAddress']['zipcode']);
        static::assertEquals('Köln', $data['billingAddress']['city']);
        static::assertTrue(Uuid::isValid($data['billingAddress']['countryId']), $data['billingAddress']['countryId'] . ' should be a valid UUID');
        static::assertEquals('0123456789', $data['billingAddress']['phone']);
    }

    public function testItRemovesShippingAddressIfShippingAddressIsIncomplete(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        /** @var CustomerRegistrationUtil $util */
        $util = $this->getContainer()->get(CustomerRegistrationUtil::class);

        $data = $util->getCustomerDataBagFromGetCheckoutSessionResponse([
            'addpaydata' => [
                'email' => 'test@localhost.local',

                // Complete
                'telephonenumber' => '0123456789',
                'firstname' => 'Petra',
                'lastname' => 'Musterfrau',
                'street' => 'pay-street 123',
                'addressaddition' => 'c/o Petra',
                'zip' => '65432',
                'city' => 'Berlin',
                'state' => 'Ohio',
                'country' => 'DE',

                // Firstname and lastname missing
                'shipping_telephonenumber' => '0123456789',
                'shipping_company' => 'your-company',
                'shipping_street' => 'refund-street 123',
                'shipping_addressaddition' => 'c/o Max',
                'shipping_zip' => '98765',
                'shipping_city' => 'Köln',
                'shipping_state' => 'New York',
                'shipping_country' => 'AT',
            ],
        ], $salesChannelContext)->all();

        static::assertArrayHasKey('billingAddress', $data);
        static::assertArrayNotHasKey('shippingAddress', $data, 'shipping address should not be present, because it was incomplete');
    }

    protected static function billingAddressDataProvider(): array
    {
        return [
            [''],
            ['billing_'],
        ];
    }
}
