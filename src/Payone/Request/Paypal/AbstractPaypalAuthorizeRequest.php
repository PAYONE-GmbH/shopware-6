<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractPaypalAuthorizeRequest
{
    /** @var RedirectHandler */
    protected $redirectHandler;

    /** @var EntityRepositoryInterface */
    protected $currencyRepository;

    /** @var ConfigReaderInterface */
    protected $configReader;

    public function __construct(
        RedirectHandler $redirectHandler,
        EntityRepositoryInterface $currencyRepository,
        ConfigReaderInterface $configReader
    ) {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
        $this->configReader       = $configReader;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $referenceNumber,
        ?CustomerAddressEntity $shippingAddress = null,
        ?string $workOrderId = null
    ): array {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $parameters = [
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $referenceNumber,
            'successurl'   => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
            'workorderid'  => $workOrderId,
        ];

        if ($shippingAddress !== null) {
            $parameters = $this->applyShippingParameters($parameters, $shippingAddress);
        }

        if ($this->isNarrativeTextAllowed($transaction->getOrder()->getSalesChannelId()) && !empty($transaction->getOrder()->getOrderNumber())) {
            $parameters['narrative_text'] = mb_substr($transaction->getOrder()->getOrderNumber(), 0, 81);
        }

        return array_filter($parameters);
    }

    protected function isNarrativeTextAllowed(string $salesChannelId): bool
    {
        $config = $this->configReader->read($salesChannelId);

        return $config->get(sprintf('%sProvideNarrativeText', ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYPAL), false);
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

    /**
     * refactor: This is as duplicate code fragment
     */
    private function applyShippingParameters(array $parameters, CustomerAddressEntity $shippingAddress): array
    {
        $shippingParameters = array_filter([
            'shipping_firstname' => $shippingAddress->getFirstName(),
            'shipping_lastname'  => $shippingAddress->getLastName(),
            'shipping_company'   => $shippingAddress->getCompany(),
            'shipping_street'    => $shippingAddress->getStreet(),
            'shipping_zip'       => $shippingAddress->getZipcode(),
            'shipping_city'      => $shippingAddress->getCity(),
            'shipping_country'   => $shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null,
        ]);

        return array_merge($parameters, $shippingParameters);
    }
}
