<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecureInvoice;

use DateTime;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CustomerInformationRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var EntityRepositoryInterface */
    private $orderAddressRepository;

    public function __construct(EntityRepositoryInterface $orderAddressRepository)
    {
        $this->orderAddressRepository = $orderAddressRepository;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $paymentTransaction = $arguments->getPaymentTransaction();
        $dataBag            = $arguments->getRequestData();
        $order              = $paymentTransaction->getOrder();
        $customer           = $order->getOrderCustomer();
        $billingAddress     = $this->getBillingAddress($order, $arguments->getSalesChannelContext()->getContext());

        if ($customer !== null) {
            $parameters['email'] = $customer->getEmail();
        }

        $company                        = $billingAddress->getCompany();
        $parameters['businessrelation'] = PayoneSecureInvoice::BUSINESSRELATION_B2C;

        if (!empty($company)) {
            $parameters['company']          = $company;
            $parameters['businessrelation'] = PayoneSecureInvoice::BUSINESSRELATION_B2B;

            return $parameters;
        }

        if (!empty($dataBag->get('secureInvoiceBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('secureInvoiceBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        return $paymentMethod === PayoneSecureInvoicePaymentHandler::class;
    }

    private function getBillingAddress(OrderEntity $order, Context $context): OrderAddressEntity
    {
        $criteria = new Criteria([$order->getBillingAddressId()]);

        /** @var null|OrderAddressEntity $address */
        $address = $this->orderAddressRepository->search($criteria, $context)->first();

        if (null === $address) {
            throw new RuntimeException('missing order customer billing address');
        }

        return $address;
    }
}
