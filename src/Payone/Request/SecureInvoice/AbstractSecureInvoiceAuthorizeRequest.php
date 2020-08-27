<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SecureInvoice;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
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
use PayonePayment\PaymentMethod\PayoneSecureInvoice;

abstract class AbstractSecureInvoiceAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    /** @var EntityRepositoryInterface */
    private $orderAddressRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $orderAddressRepository,
        ConfigReaderInterface $configReader
    ) {
        $this->currencyRepository     = $currencyRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->configReader           = $configReader;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        string $referenceNumber
    ): array {
        $order = $transaction->getOrder();
        $currency = $this->getOrderCurrency($order, $context->getContext());
        $billingAddress = $this->getBillingAddress($order, $context->getContext());
        $centAmountTotal =
            (int) round(($order->getAmountTotal() * (10 ** $currency->getDecimalPrecision())));

        $parameters = [
            'clearingtype'  => 'rec',
            'financingtype' => 'POX',
            'amount'        => $centAmountTotal,
            'currency'      => $currency->getIsoCode(),
            'reference'     => $referenceNumber,
        ];

        $company = $billingAddress->getCompany();

        $parameters['businessrelation'] = $company ?
            PayoneSecureInvoice::BUSINESSRELATION_B2B :
            PayoneSecureInvoice::BUSINESSRELATION_B2C;

        if ($company) {
            $parameters['company'] = $billingAddress->getCompany();
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
