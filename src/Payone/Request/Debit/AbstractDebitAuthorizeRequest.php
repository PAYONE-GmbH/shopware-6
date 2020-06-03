<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractDebitAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $iban,
        string $bic,
        string $accountOwner
    ): array {
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        return [
            'clearingtype'      => 'elv',
            'iban'              => $iban,
            'bic'               => $bic,
            'bankaccountholder' => $accountOwner,
            'amount'            => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'          => $currency->getIsoCode(),
            'reference'         => $transaction->getOrder()->getOrderNumber(),
        ];
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
}
