<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Prepayment;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPrepaymentAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    protected $currencyRepository;

    /** @var EntityRepositoryInterface */
    protected $orderAddressRepository;

    /** @var ConfigReaderInterface */
    protected $configReader;

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
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context->getContext());

        $parameters = [
            'clearingtype' => 'vor',
            'amount'       => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $referenceNumber,
        ];

        if ($this->isNarrativeTextAllowed($transaction->getOrder()->getSalesChannelId()) && !empty($transaction->getOrder()->getOrderNumber())) {
            $parameters['narrative_text'] = mb_substr($transaction->getOrder()->getOrderNumber(), 0, 81);
        }

        return array_filter($parameters);
    }

    protected function isNarrativeTextAllowed(string $salesChannelId): bool
    {
        $config = $this->configReader->read($salesChannelId);

        return $config->get(sprintf('%sProvideNarrativeText', ConfigurationPrefixes::CONFIGURATION_PREFIX_PREPAYMENT), false);
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
