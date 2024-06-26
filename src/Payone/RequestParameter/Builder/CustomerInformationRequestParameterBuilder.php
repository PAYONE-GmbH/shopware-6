<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\RequestConstants;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CustomerInformationRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $parameters = [];
        $paymentTransaction = $arguments->getPaymentTransaction();
        $dataBag = $arguments->getRequestData();
        $order = $paymentTransaction->getOrder();
        $customer = $order->getOrderCustomer();
        $billingAddress = $this->getBillingAddress($order, $arguments->getSalesChannelContext()->getContext());

        if ($customer !== null) {
            $parameters['email'] = $customer->getEmail();
        }

        $company = $billingAddress->getCompany();
        $parameters['businessrelation'] = PayoneSecureInvoice::BUSINESSRELATION_B2C;

        if (!empty($company)) {
            $parameters['company'] = $company;
            $parameters['businessrelation'] = PayoneSecureInvoice::BUSINESSRELATION_B2B;

            return $parameters;
        }

        if (!empty($dataBag->get(RequestConstants::BIRTHDAY))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get(RequestConstants::BIRTHDAY));

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

        return \in_array($arguments->getPaymentMethod(), [
            PayoneSecureInvoicePaymentHandler::class,
            PayoneOpenInvoicePaymentHandler::class,
            PayoneSecuredInvoicePaymentHandler::class,
            PayoneSecuredInstallmentPaymentHandler::class,
            PayoneSecuredDirectDebitPaymentHandler::class,
        ], true);
    }

    private function getBillingAddress(OrderEntity $order, Context $context): OrderAddressEntity
    {
        $criteria = new Criteria([$order->getBillingAddressId()]);

        /** @var OrderAddressEntity|null $address */
        $address = $this->serviceAccessor->orderAddressRepository->search($criteria, $context)->first();

        if ($address === null) {
            throw new \RuntimeException('missing order customer billing address');
        }

        return $address;
    }
}
