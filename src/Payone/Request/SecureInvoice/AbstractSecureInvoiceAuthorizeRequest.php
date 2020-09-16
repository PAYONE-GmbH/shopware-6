<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SecureInvoice;

use DateTime;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractSecureInvoiceAuthorizeRequest
{
    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    /** @var EntityRepositoryInterface */
    private $orderAddressRepository;

    public function __construct(
        LineItemHydratorInterface $lineItemHydrator,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $orderAddressRepository
    ) {
        $this->lineItemHydrator       = $lineItemHydrator;
        $this->currencyRepository     = $currencyRepository;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        string $referenceNumber
    ): array {
        $order           = $transaction->getOrder();
        $customer        = $order->getOrderCustomer();
        $currency        = $this->getOrderCurrency($order, $context->getContext());
        $billingAddress  = $this->getBillingAddress($order, $context->getContext());
        $centAmountTotal = (int) round(($order->getAmountTotal() * (10 ** $currency->getDecimalPrecision())));

        $parameters = [
            'clearingtype'    => 'rec',
            'clearingsubtype' => 'POV',
            'amount'          => $centAmountTotal,
            'currency'        => $currency->getIsoCode(),
            'reference'       => $referenceNumber,
        ];

        $company = $billingAddress->getCompany();

        if ($customer !== null) {
            $parameters['email'] = $customer->getEmail();
        }

        $parameters['businessrelation'] = $company ?
            PayoneSecureInvoice::BUSINESSRELATION_B2B :
            PayoneSecureInvoice::BUSINESSRELATION_B2C;

        if (!empty($company)) {
            $parameters['company'] = $billingAddress->getCompany();
        }

        $parameters = array_merge($parameters, $this->lineItemHydrator->mapOrderLines($currency, $order->getLineItems()));

        if (!$company && !empty($dataBag->get('secureInvoiceBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('secureInvoiceBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return array_filter($parameters);
    }

    private function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
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
