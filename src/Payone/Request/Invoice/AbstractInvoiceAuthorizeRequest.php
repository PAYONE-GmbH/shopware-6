<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Invoice;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
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

abstract class AbstractInvoiceAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    /** @var EntityRepositoryInterface */
    private $orderAddressRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;

    public function __construct(
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $orderAddressRepository,
        ConfigReaderInterface $configReader,
        LineItemHydratorInterface $lineItemHydrator
    ) {
        $this->currencyRepository     = $currencyRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->configReader           = $configReader;
        $this->lineItemHydrator       = $lineItemHydrator;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        string $referenceNumber
    ): array {
        $order          = $transaction->getOrder();
        $customer       = $order->getOrderCustomer();
        $currency       = $this->getOrderCurrency($order, $context->getContext());
        $amount         = (int) round(($order->getAmountTotal() * (10 ** $currency->getDecimalPrecision())));
        $billingAddress = $this->getBillingAddress($order, $context->getContext());
        $company        = $billingAddress->getCompany();

        $parameters = [
            'clearingtype'     => 'rec',
            'amount'           => $amount,
            'currency'         => $currency->getIsoCode(),
            'reference'        => $referenceNumber,
            'businessrelation' => 'b2c',
        ];

        if ($customer) {
            $parameters['email'] = $customer->getEmail();
        }

        if (!empty($company)) {
            $parameters['company']          = $company;
            $parameters['businessrelation'] = 'b2c';
        }

        $parameters = array_merge($parameters, $this->lineItemHydrator->mapOrderLines($currency, $order->getLineItems()));

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
