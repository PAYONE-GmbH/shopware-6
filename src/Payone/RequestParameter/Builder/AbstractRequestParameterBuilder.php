<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\RequestConstants;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractRequestParameterBuilder
{
    final public const REQUEST_ACTION_AUTHORIZE = 'authorization';
    final public const REQUEST_ACTION_PREAUTHORIZE = 'preauthorization';
    final public const REQUEST_ACTION_CAPTURE = 'capture';
    final public const REQUEST_ACTION_REFUND = 'refund';
    final public const REQUEST_ACTION_TEST = 'test';
    final public const REQUEST_ACTION_GET_EXPRESS_CHECKOUT_DETAILS = 'getexpresscheckoutdetails';
    final public const REQUEST_ACTION_SET_EXPRESS_CHECKOUT = 'setexpresscheckout';
    final public const REQUEST_ACTION_PAYOLUTION_PRE_CHECK = 'pre-check';
    final public const REQUEST_ACTION_PAYOLUTION_CALCULATION = 'calculation';
    final public const REQUEST_ACTION_GENERIC_PAYMENT = 'genericpayment';
    final public const REQUEST_ACTION_CREDITCARD_CHECK = 'creditcardcheck';
    final public const REQUEST_ACTION_GET_FILE = 'getfile';
    final public const REQUEST_ACTION_MANAGE_MANDATE = 'managemandate';
    final public const REQUEST_ACTION_DEBIT = 'debit';
    final public const REQUEST_ACTION_RATEPAY_PROFILE = 'ratepayProfile';
    final public const REQUEST_ACTION_RATEPAY_CALCULATION = 'ratepayCalculation';
    final public const REQUEST_ACTION_SECURED_INSTALLMENT_OPTIONS = 'securedInstallmentOptions';

    final public const CLEARING_TYPE_DEBIT = 'elv';
    final public const CLEARING_TYPE_WALLET = 'wlt';
    final public const CLEARING_TYPE_FINANCING = 'fnc';
    final public const CLEARING_TYPE_CREDIT_CARD = 'cc';
    final public const CLEARING_TYPE_PREPAYMENT = 'vor';
    final public const CLEARING_TYPE_ONLINE_BANK_TRANSFER = 'sb';
    final public const CLEARING_TYPE_INVOICE = 'rec';

    public function __construct(
        protected readonly RequestBuilderServiceAccessor $serviceAccessor
    ) {
    }

    abstract public function getRequestParameter(AbstractRequestParameterStruct $arguments): array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(AbstractRequestParameterStruct $arguments): bool;

    protected function getOrderCurrency(?OrderEntity $order, Context $context): CurrencyEntity
    {
        if ($order !== null && $order->getCurrency() !== null) {
            return $order->getCurrency();
        }

        $currencyId = $context->getCurrencyId();

        if ($order !== null) {
            $currencyId = $order->getCurrencyId();
        }

        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyEntity|null $currency */
        $currency = $this->serviceAccessor->currencyRepository->search($criteria, $context)->first();

        if ($currency === null) {
            throw new \RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    /**
     * throws an exception if the given $method does not exist on the given $object
     */
    protected function validateMethod(object $object, string $method): void
    {
        if (!method_exists($object, $method)) {
            // there is no function to get the salesChannelContext. Without it the builder is not able to get the customer data
            throw new \RuntimeException(sprintf('method `%s` does not exist on %s', $method, $object::class));
        }
    }

    protected function applyPhoneParameter(OrderEntity $order, array &$parameters, ParameterBag $dataBag, Context $context, bool $isOptional = false): void
    {
        $phoneNumber = $dataBag->get(RequestConstants::PHONE);

        if (empty($phoneNumber)) {
            $orderAddress = $order->getBillingAddress();
            if ($orderAddress === null) {
                /** @var OrderAddressEntity|null $orderAddress */
                $orderAddress = $this->serviceAccessor->orderAddressRepository->search(new Criteria([$order->getBillingAddressId()]), $context)->first();
            }
            $phoneNumber = $orderAddress?->getPhoneNumber();
        }

        if (empty($phoneNumber)) {
            if (!$isOptional) {
                throw new \RuntimeException('missing phone number');
            }

            return;
        }

        $parameters['telephonenumber'] = $phoneNumber;
    }

    protected function applyBirthdayParameter(OrderEntity $order, array &$parameters, ParameterBag $dataBag, Context $context, bool $isOptional = false): void
    {
        $birthday = $dataBag->get(RequestConstants::BIRTHDAY);
        $birthday = \is_string($birthday) ? \DateTime::createFromFormat('Y-m-d', $birthday) ?: null : null;

        $birthday = $birthday instanceof \DateTimeInterface ? $birthday : $order->getOrderCustomer()?->getCustomer()?->getBirthday();

        if (!$birthday instanceof \DateTimeInterface) {
            if (!$isOptional) {
                throw new \RuntimeException('missing birthday');
            }

            return;
        }

        $parameters['birthday'] = $birthday->format('Ymd');
    }

    protected function orderNotFoundException(string $orderId): \Throwable
    {
        if (class_exists(PaymentException::class)) {
            return PaymentException::invalidOrder($orderId);
        } elseif (class_exists(InvalidOrderException::class)) {
            // required for shopware version <= 6.5.3
            throw new InvalidOrderException($orderId); // @phpstan-ignore-line
        }

        // should never occur, just to be safe.
        throw new \RuntimeException('invalid order ' . $orderId);
    }
}
