<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paydirekt;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractPaydirektAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(
        RedirectHandler $redirectHandler,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        CustomerAddressEntity $shippingAddress
    ): array {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $parameters = [
            'clearingtype' => 'wlt',
            'wallettype'   => 'PDT',
            'amount'       => (int) ($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision())),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $transaction->getOrder()->getOrderNumber(),
            'successurl'   => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
        ];

        return $this->applyShippingParameters($parameters, $shippingAddress);
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

    private function applyShippingParameters(array $parameters, CustomerAddressEntity $shippingAddress): array
    {
        $shippingParameters = array_filter([
            'shipping_firstname' => $shippingAddress->getFirstName(),
            'shipping_lastname'  => $shippingAddress->getLastName(),
            'shipping_company'   => $shippingAddress->getCompany(),
            'shipping_street'    => $shippingAddress->getStreet(),
            'shipping_zip'       => $shippingAddress->getZipcode(),
            'shipping_city'      => $shippingAddress->getCity(),
            'shipping_country'   => $shippingAddress->getCountry() ? $shippingAddress->getCountry()->getIso() : null,
        ]);

        return array_merge($parameters, $shippingParameters);
    }
}
