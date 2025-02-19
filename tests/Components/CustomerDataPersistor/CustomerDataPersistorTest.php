<?php

declare(strict_types=1);

namespace PayonePayment\Components\CustomerDataPersistor;

use DateTime;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerDataPersistorTest extends TestCase
{
    use PayoneTestBehavior;

    private SalesChannelContext $context;

    private EntityRepository $orderRepository;

    private EntityRepository $customerRepository;

    private EntityRepository $customerAddressRepository;

    protected function setUp(): void
    {
        $this->getContainer()->get(SystemConfigService::class)->set('PayonePayment.settings.saveUserEnteredDataToCustomer', true);
        $this->context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->orderRepository = $this->getContainer()->get('order.repository');
        $this->customerRepository = $this->getContainer()->get('customer.repository');
        $this->customerAddressRepository = $this->getContainer()->get('customer_address.repository');
    }

    public function testIfPhoneNumberGotSaved(): void
    {
        $defaultBillingAddress = $this->context->getCustomer()->getDefaultBillingAddress();
        $defaultBillingAddress->setPhoneNumber('old-phone-number');
        $this->customerAddressRepository->update([[
            'id' => $defaultBillingAddress->getId(),
            'phoneNumber' => $defaultBillingAddress->getPhoneNumber(),
        ]], $this->context->getContext());
        $order = $this->getRandomOrder($this->context);

        $this->getContainer()->get(CustomerDataPersistor::class)->save($order, new RequestDataBag([
            RequestConstants::PHONE => 'test-phone-number_address',
        ]), $this->context->getContext());

        $criteria = new Criteria([$order->getId()]);
        $criteria->addAssociation('billingAddress');
        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->context->getContext())->first();
        static::assertEquals('test-phone-number_address', $order->getBillingAddress()->getPhoneNumber());

        /** @var CustomerAddressEntity $defaultBillingAddress */
        $defaultBillingAddress = $this->customerAddressRepository->search(new Criteria([$defaultBillingAddress->getId()]), $this->context->getContext())->first();
        static::assertEquals('test-phone-number_address', $defaultBillingAddress->getPhoneNumber());
    }

    /**
     * @dataProvider phoneNumberGotNotSavedDataProvider
     */
    public function testIfEmptyPhoneNumberGotNotSaved(?string $input = null): void
    {
        $defaultBillingAddress = $this->context->getCustomer()->getDefaultBillingAddress();
        $defaultBillingAddress->setPhoneNumber('old-phone-number');
        $this->customerAddressRepository->update([[
            'id' => $defaultBillingAddress->getId(),
            'phoneNumber' => $defaultBillingAddress->getPhoneNumber(),
        ]], $this->context->getContext());
        $order = $this->getRandomOrder($this->context);

        $this->getContainer()->get(CustomerDataPersistor::class)->save($order, new RequestDataBag([
            RequestConstants::PHONE => $input,
        ]), $this->context->getContext());

        $criteria = new Criteria([$order->getId()]);
        $criteria->addAssociation('billingAddress');
        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->context->getContext())->first();
        static::assertEquals('old-phone-number', $order->getBillingAddress()->getPhoneNumber());

        /** @var CustomerAddressEntity $defaultBillingAddress */
        $defaultBillingAddress = $this->customerAddressRepository->search(new Criteria([$defaultBillingAddress->getId()]), $this->context->getContext())->first();
        static::assertEquals('old-phone-number', $defaultBillingAddress->getPhoneNumber());
    }

    public function testIfBirthDayGotSaved(): void
    {
        $customer = $this->context->getCustomer();
        $customer->setBirthday(DateTime::createFromFormat('Y-m-d', '1950-12-20'));
        $this->customerRepository->update([[
            'id' => $customer->getId(),
            'birthday' => $customer->getBirthday(),
        ]], $this->context->getContext());
        $order = $this->getRandomOrder($this->context);

        $this->getContainer()->get(CustomerDataPersistor::class)->save($order, new RequestDataBag([
            RequestConstants::BIRTHDAY => '2020-11-18',
        ]), $this->context->getContext());

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(new Criteria([$customer->getId()]), $this->context->getContext())->first();
        static::assertEquals('2020-11-18', $customer->getBirthday()->format('Y-m-d'));
    }

    /**
     * @dataProvider birthDayGotNotSavedDataProvider
     */
    public function testIfEmptyBirthDayGotNotSaved(?string $input = null): void
    {
        $customer = $this->context->getCustomer();
        $customer->setBirthday(DateTime::createFromFormat('Y-m-d', '1950-12-20'));
        $this->customerRepository->update([[
            'id' => $customer->getId(),
            'birthday' => $customer->getBirthday(),
        ]], $this->context->getContext());
        $order = $this->getRandomOrder($this->context);

        $this->getContainer()->get(CustomerDataPersistor::class)->save($order, new RequestDataBag([
            RequestConstants::BIRTHDAY => $input,
        ]), $this->context->getContext());

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(new Criteria([$customer->getId()]), $this->context->getContext())->first();
        static::assertEquals('1950-12-20', $customer->getBirthday()->format('Y-m-d'));
    }

    public static function birthDayGotNotSavedDataProvider(): array
    {
        return [
            [null],
            [''],
            [' '],
            ['invalid-value'],
        ];
    }

    public static function phoneNumberGotNotSavedDataProvider(): array
    {
        return [
            [null],
            [''],
            [' '],
        ];
    }
}
