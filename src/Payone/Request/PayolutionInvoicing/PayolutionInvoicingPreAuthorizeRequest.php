<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInvoicing;

use DateTime;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PayolutionInvoicingPreAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $request = [
            'request'       => 'preauthorization',
            'clearingtype'  => 'fnc',
            'financingtype' => 'PYV',
            'amount'        => (int) ($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision())),
            'currency'      => $currency->getIsoCode(),
            'reference'     => $transaction->getOrder()->getOrderNumber(),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $request['birthday'] = $birthday->format('Ymd');
            }
        }

        $address = $this->getOrderBillingAddress($transaction->getOrder());

        if (null === $address) {
            throw new RuntimeException('missing order customer billing address');
        }

        if ($address->getCompany()) {
            $request['add_paydata[b2b]'] = 'yes';
        }

        return array_filter($request);
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

    private function getOrderBillingAddress(OrderEntity $order): ?OrderAddressEntity
    {
        foreach ($order->getAddresses() as $address) {
            if ($address->getId() === $order->getBillingAddressId()) {
                return $address;
            }
        }

        return null;
    }
}
