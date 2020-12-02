<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionDebit;

use DateTime;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
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

abstract class AbstractPayolutionDebitAuthorizeRequest
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
            'clearingtype'  => 'fnc',
            'financingtype' => 'PYD',
            'amount'        => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'      => $currency->getIsoCode(),
            'reference'     => $referenceNumber,
            'iban'          => $dataBag->get('payolutionIban'),
            'bic'           => $dataBag->get('payolutionBic'),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        if (!empty($dataBag->get('workorder'))) {
            $parameters['workorderid'] = $dataBag->get('workorder');
        }

        if ($this->transferCompanyData($context)) {
            $billingAddress = $this->getBillingAddress($transaction->getOrder(), $context->getContext());

            if ($billingAddress->getCompany()) {
                $parameters['add_paydata[b2b]']         = 'yes';
                $parameters['add_paydata[company_uid]'] = $billingAddress->getVatId();
            }
        }

        if ($this->isNarrativeTextAllowed($transaction->getOrder()->getSalesChannelId()) && !empty($transaction->getOrder()->getOrderNumber())) {
            $parameters['narrative_text'] = mb_substr($transaction->getOrder()->getOrderNumber(), 0, 81);
        }

        return array_filter($parameters);
    }

    protected function isNarrativeTextAllowed(string $salesChannelId): bool
    {
        $config = $this->configReader->read($salesChannelId);

        return $config->get(sprintf('%sProvideNarrativeText', ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYOLUTION_DEBIT), false);
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

    private function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get('payolutionDebitTransferCompanyData'));
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
