<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
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
    protected $currencyRepository;

    /** @var ConfigReaderInterface */
    protected $configReader;

    public function __construct(EntityRepositoryInterface $currencyRepository, ConfigReaderInterface $configReader)
    {
        $this->currencyRepository = $currencyRepository;
        $this->configReader       = $configReader;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $iban,
        string $bic,
        string $accountOwner,
        string $referenceNumber
    ): array {
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $parameters = [
            'clearingtype'      => 'elv',
            'iban'              => $iban,
            'bic'               => $bic,
            'bankaccountholder' => $accountOwner,
            'amount'            => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'          => $currency->getIsoCode(),
            'reference'         => $referenceNumber,
        ];

        if ($this->isNarrativeTextAllowed($transaction->getOrder()->getSalesChannelId()) && !empty($transaction->getOrder()->getOrderNumber())) {
            $parameters['narrative_text'] = mb_substr($transaction->getOrder()->getOrderNumber(), 0, 81);
        }

        return $parameters;
    }

    protected function isNarrativeTextAllowed(string $salesChannelId): bool
    {
        $config = $this->configReader->read($salesChannelId);

        return $config->get(sprintf('%sProvideNarrativeText', ConfigurationPrefixes::CONFIGURATION_PREFIX_DEBIT), false);
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
