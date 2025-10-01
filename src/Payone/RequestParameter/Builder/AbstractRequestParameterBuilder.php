<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @deprecated
 */
abstract class AbstractRequestParameterBuilder
{
    public function __construct(
        protected readonly RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    abstract public function getRequestParameter(AbstractRequestParameterStruct $arguments): array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(AbstractRequestParameterStruct $arguments): bool;

    protected function getOrderCurrency(?OrderEntity $order, Context $context): CurrencyEntity
    {
        if (null !== $order && null !== $order->getCurrency()) {
            return $order->getCurrency();
        }

        $currencyId = $context->getCurrencyId();

        if (null !== $order) {
            $currencyId = $order->getCurrencyId();
        }

        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyEntity|null $currency */
        $currency = $this->serviceAccessor->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
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

    protected function applyPhoneParameter(
        OrderEntity $order,
        array &$parameters,
        ParameterBag $dataBag,
        Context $context,
        bool $isOptional = false,
    ): void {
        $phoneNumber = $dataBag->get(RequestConstantsEnum::PHONE->value);

        if (empty($phoneNumber)) {
            $orderAddress = $order->getBillingAddress();
            if (null === $orderAddress) {
                /** @var OrderAddressEntity|null $orderAddress */
                $orderAddress = $this->serviceAccessor->orderAddressRepository->search(
                    new Criteria([ $order->getBillingAddressId() ]),
                    $context,
                )->first();
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

    protected function applyBirthdayParameter(
        OrderEntity $order,
        array &$parameters,
        ParameterBag $dataBag,
        Context $context,
        bool $isOptional = false,
    ): void {
        $birthday = $dataBag->get(RequestConstantsEnum::BIRTHDAY->value);
        $birthday = \is_string($birthday) ? \DateTime::createFromFormat('Y-m-d', $birthday) ?: null : null;

        $birthday = $birthday instanceof \DateTimeInterface
            ? $birthday
            : $order->getOrderCustomer()?->getCustomer()?->getBirthday()
        ;

        if (!$birthday instanceof \DateTimeInterface) {
            if (!$isOptional) {
                throw new \RuntimeException('missing birthday');
            }

            return;
        }

        $parameters['birthday'] = $birthday->format('Ymd');
    }

    protected function orderNotFoundException(string $orderId): PaymentException
    {
        return PaymentException::invalidOrder($orderId);
    }
}
