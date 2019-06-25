<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
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

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        EntityRepositoryInterface $currencyRepository,
        ConfigReaderInterface $configReader
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->configReader       = $configReader;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $iban,
        string $bic,
        string $accountOwner
    ): array {
        $config    = $this->configReader->read($transaction->getOrder()->getSalesChannelId());
        $reference = $transaction->getOrder()->getOrderNumber();

        if (!empty($config->get('ordernumberPrefix'))) {
            $reference = $config->get('ordernumberPrefix') . $reference;
        }

        return [
            'request'           => 'authorization',
            'clearingtype'      => 'elv',
            'iban'              => $iban,
            'bic'               => $bic,
            'bankaccountholder' => $accountOwner,
            'amount'            => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'          => $this->getOrderCurrency($transaction->getOrder(), $context)->getIsoCode(),
            'reference'         => $reference,
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
