<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaypalAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(RedirectHandler $redirectHandler, EntityRepositoryInterface $currencyRepository)
    {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(PaymentTransaction $transaction, Context $context): array
    {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        return [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'     => $this->getOrderCurrency($transaction->getOrder(), $context)->getIsoCode(),
            'reference'    => $transaction->getOrder()->getOrderNumber(),
            'successurl'   => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
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
