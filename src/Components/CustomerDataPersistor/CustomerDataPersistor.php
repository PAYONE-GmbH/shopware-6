<?php

declare(strict_types=1);

namespace PayonePayment\Components\CustomerDataPersistor;

use PayonePayment\RequestConstants;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerDataPersistor
{
    public function __construct(
        private readonly EntityRepository $orderAddressRepository,
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $customerAddressRepository,
        private readonly SystemConfigService $configService
    ) {
    }

    public function save(OrderEntity $order, RequestDataBag $dataBag, Context $context): void
    {
        $this->saveOrderData($order, $dataBag, $context);

        if ($this->configService->getBool('PayonePayment.settings.saveUserEnteredDataToCustomer')) {
            $customer = $this->getCustomer($order, $context);
            if ($customer instanceof CustomerEntity) {
                $this->saveCustomerData($customer, $dataBag, $context);
                $customerAddress = $this->getCustomerAddress($customer, $context);
                if ($customerAddress instanceof CustomerAddressEntity) {
                    $this->saveCustomerAddressData($customerAddress, $dataBag, $context);
                }
            }
        }
    }

    protected function saveCustomerData(CustomerEntity $customer, RequestDataBag $dataBag, Context $context): void
    {
        $birthday = $dataBag->get(RequestConstants::BIRTHDAY);
        $birthday = \is_string($birthday) ? (\DateTime::createFromFormat('Y-m-d', $birthday) ?: null) : null;

        if ($birthday instanceof \DateTime && $birthday->getTimestamp() !== $customer->getBirthday()?->getTimestamp()) {
            $this->customerRepository->update(
                [
                    [
                        'id' => $customer->getId(),
                        'birthday' => $birthday,
                    ],
                ],
                $context
            );
        }
    }

    protected function saveCustomerAddressData(CustomerAddressEntity $customerAddress, RequestDataBag $dataBag, Context $context): void
    {
        $phoneNumber = $dataBag->get(RequestConstants::PHONE);

        if ($phoneNumber !== $customerAddress->getPhoneNumber()) {
            $this->customerAddressRepository->update(
                [
                    [
                        'id' => $customerAddress->getId(),
                        'phoneNumber' => $phoneNumber,
                    ],
                ],
                $context
            );
        }
    }

    protected function saveOrderData(OrderEntity $orderEntity, RequestDataBag $dataBag, Context $context): void
    {
        $phoneNumber = $dataBag->get(RequestConstants::PHONE);

        $this->orderAddressRepository->update(
            [
                [
                    'id' => $orderEntity->getBillingAddressId(),
                    'phoneNumber' => $phoneNumber,
                ],
            ],
            $context
        );
    }

    private function getCustomer(OrderEntity $order, Context $context): ?CustomerEntity
    {
        $customer = $order->getOrderCustomer()?->getCustomer();
        if ($customer instanceof CustomerEntity) {
            return $customer;
        }
        $customerId = $order->getOrderCustomer()?->getCustomerId();
        if (empty($customerId)) {
            return null;
        }

        $criteria = new Criteria([$customerId]);
        $criteria->addAssociation('defaultBillingAddress');

        /** @var CustomerEntity|null $customer */
        $customer = $this->customerRepository->search($criteria, $context)->first();

        return $customer;
    }

    private function getCustomerAddress(CustomerEntity $customer, Context $context): ?CustomerAddressEntity
    {
        $customerAddress = $customer->getDefaultBillingAddress();
        if ($customerAddress instanceof CustomerAddressEntity) {
            return $customerAddress;
        }

        /** @var CustomerAddressEntity|null $customerAddress */
        $customerAddress = $this->customerAddressRepository->search(
            new Criteria([$customer->getDefaultBillingAddressId()]),
            $context
        )->first();

        return $customerAddress;
    }
}
