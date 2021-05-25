<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

abstract class AbstractRequestParameterBuilder {
    //TODO: add action constants

    protected RedirectHandler $redirectHandler;
    protected EntityRepositoryInterface $currencyRepository;

    abstract public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ) : array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(string $paymentMethod, string $action = '') : bool;

    protected function getConvertedAmount(float $amount, int $precision) : int {
        return (int) round($amount * (10 ** $precision));
    }

    protected function encodeUrl(string $url) : string {
        return $this->redirectHandler->encode($url);
    }

    protected function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
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
