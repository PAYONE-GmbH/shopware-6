<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\RequestParameter\Struct\CheckoutDetailsStruct;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractRequestParameterBuilder
{
    public const REQUEST_ACTION_AUTHORIZE                    = 'authorization';
    public const REQUEST_ACTION_PREAUTHORIZE                 = 'preauthorization';
    public const REQUEST_ACTION_TEST                         = 'test';
    public const REQUEST_ACTION_GET_EXPRESS_CHECKOUT_DETAILS = 'getexpresscheckoutdetails';
    public const REQUEST_ACTION_SET_EXPRESS_CHECKOUT         = 'setexpresscheckout';
    public const REQUEST_ACTION_PAYOLUTION_PRE_CHECK         = 'pre-check';
    public const REQUEST_ACTION_PAYOLUTION_CALCULATION       = 'calculation';

    /** @param CheckoutDetailsStruct|CreditCardCheckStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct|TestCredentialsStruct $arguments */
    abstract public function getRequestParameter(
        Struct $arguments
    ): array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */

    /** @param CheckoutDetailsStruct|CreditCardCheckStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct|TestCredentialsStruct $arguments */
    abstract public function supports(Struct $arguments): bool;

    protected function getConvertedAmount(float $amount, int $precision): int
    {
        return (int) round($amount * (10 ** $precision));
    }

    protected function getOrderCurrency(?OrderEntity $order, Context $context): CurrencyEntity
    {
        if (property_exists($this, 'currencyRepository') === false) {
            throw new RuntimeException('currency repository injection missing');
        }

        $currencyId = $context->getCurrencyId();

        if (null !== $order) {
            $currencyId = $order->getCurrencyId();
        }

        $criteria = new Criteria([$currencyId]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }
}
