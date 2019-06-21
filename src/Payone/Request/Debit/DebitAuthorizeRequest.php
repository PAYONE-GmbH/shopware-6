<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class DebitAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $iban,
        string $bic,
        string $accountOwner
    ): array {
        return [
            'request'           => 'authorization',
            'clearingtype'      => 'elv',
            'iban'              => $iban,
            'bic'               => $bic,
            'bankaccountholder' => $accountOwner,
            'amount'            => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'          => $this->getOrderCurrency($transaction->getOrder(), $context)->getIsoCode(),
            'reference'         => $transaction->getOrder()->getOrderNumber(),
        ];
    }

    private function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $language */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $language) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }
}
