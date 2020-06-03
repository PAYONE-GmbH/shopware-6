<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractPaypalAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(
        RedirectHandler $redirectHandler,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        ?string $workOrderId = null
    ): array {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        return array_filter([
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $transaction->getOrder()->getOrderNumber(),
            'successurl'   => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
            'workorderid'  => $workOrderId,
        ]);
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
