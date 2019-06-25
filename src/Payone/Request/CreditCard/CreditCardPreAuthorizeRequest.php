<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class CreditCardPreAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        RedirectHandler $redirectHandler,
        EntityRepositoryInterface $currencyRepository,
        ConfigReaderInterface $configReader
    ) {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
        $this->configReader       = $configReader;
    }

    public function getRequestParameters(PaymentTransaction $transaction, Context $context, string $pseudoPan): array
    {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $config    = $this->configReader->read($transaction->getOrder()->getSalesChannelId());
        $reference = $transaction->getOrder()->getOrderNumber();

        if (!empty($config->get('ordernumberPrefix'))) {
            $reference = $config->get('ordernumberPrefix') . $reference;
        }

        return [
            'request'       => 'preauthorization',
            'clearingtype'  => 'cc',
            'amount'        => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'      => $this->getOrderCurrency($transaction->getOrder(), $context)->getIsoCode(),
            'reference'     => $reference,
            'pseudocardpan' => $pseudoPan,
            'successurl'    => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'       => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
        ];
    }

    private function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $language */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }
}
