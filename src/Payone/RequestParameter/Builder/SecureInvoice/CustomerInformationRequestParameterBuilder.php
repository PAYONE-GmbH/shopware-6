<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecureInvoice;

use DateTime;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Struct;

class CustomerInformationRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var LineItemHydratorInterface */
    protected $lineItemHydrator;

    /** @var EntityRepositoryInterface */
    protected $currencyRepository;

    public function __construct(LineItemHydratorInterface $lineItemHydrator, EntityRepositoryInterface $currencyRepository)
    {
        $this->lineItemHydrator   = $lineItemHydrator;
        $this->currencyRepository = $currencyRepository;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction = $arguments->getPaymentTransaction();
        $dataBag            = $arguments->getRequestData();
        $order              = $paymentTransaction->getOrder();
        $customer           = $order->getOrderCustomer();
        $orderAddresses     = $order->getAddresses();

        if ($customer !== null) {
            $parameters['email'] = $customer->getEmail();
        }

        //TODO: we may need to get this from the repository
        if (null === $orderAddresses) {
            throw new \RuntimeException('customer order address missing');
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());
        $company        = $billingAddress->getCompany();

        $parameters['businessrelation'] = $company ?
            PayoneSecureInvoice::BUSINESSRELATION_B2B :
            PayoneSecureInvoice::BUSINESSRELATION_B2C;

        if (!empty($company)) {
            $parameters['company'] = $company;

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

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        return $paymentMethod === PayoneSecureInvoicePaymentHandler::class;
    }
}
