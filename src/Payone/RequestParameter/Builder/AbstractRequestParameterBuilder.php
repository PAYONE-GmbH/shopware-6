<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

abstract class AbstractRequestParameterBuilder {
    //TODO: add action constants

    protected RedirectHandler $redirectHandler;
    protected EntityRepositoryInterface $currencyRepository;

    abstract public function getRequestParameter(RequestContentStruct $requestContent, Context $context) : array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(RequestContentStruct $requestContent) : bool;

    /**
     * Validate if given content does provide all necessary information
     *
     * @throws InvalidRequestParameterException
     */
    abstract public function validate(RequestContentStruct $requestContent) : void;

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
