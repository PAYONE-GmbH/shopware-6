<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

class DefaultPaymentFilterService implements PaymentFilterServiceInterface
{
    /**
     * @param class-string<AbstractPayonePaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        private readonly string $paymentHandlerClass,
        private readonly ?array $allowedCountries = null,
        private readonly ?array $allowedB2bCountries = null,
        private readonly ?array $allowedCurrencies = null,
        private readonly float $allowedMinValue = 0.0,
        private readonly ?float $allowedMaxValue = null
    ) {
    }

    final public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext
    ): PaymentMethodCollection {
        if ($this->getSupportedPaymentMethods($methodCollection)->getElements() === []) {
            return $methodCollection;
        }

        $currency = $filterContext->getCurrency();
        $billingAddress = $filterContext->getBillingAddress();

        $currentValue = null;
        if ($filterContext->getCart()) {
            $currentValue = $filterContext->getCart()->getPrice()->getTotalPrice();
        } elseif ($filterContext->getOrder()) {
            $currentValue = $filterContext->getOrder()->getPrice()->getTotalPrice();
        }

        try {
            $this->validateCurrency($currency);
            $this->validateAddress($billingAddress);

            if ($currentValue) {
                $this->validateMinValue($currentValue);
                $this->validateMaxValue($currentValue);
            }
            $this->additionalChecks($methodCollection, $filterContext);
        } catch (PaymentMethodNotAllowedException) {
            $methodCollection = $this->removePaymentMethods($methodCollection);
        }

        return $methodCollection;
    }

    protected function additionalChecks(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext
    ): void {
    }

    private function getSupportedPaymentMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        $refClass = new \ReflectionClass($this->paymentHandlerClass);

        return $paymentMethodCollection->filter(fn (PaymentMethodEntity $paymentMethod) => $refClass->isAbstract()
            ? is_subclass_of($paymentMethod->getHandlerIdentifier(), $this->paymentHandlerClass)
            : $paymentMethod->getHandlerIdentifier() === $this->paymentHandlerClass);
    }

    private function removePaymentMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        $itemsToRemove = $this->getSupportedPaymentMethods($paymentMethodCollection);

        return $paymentMethodCollection->filter(static fn (PaymentMethodEntity $entity) => !$itemsToRemove->has($entity->getUniqueIdentifier()));
    }

    private function validateAddress(CustomerAddressEntity|OrderAddressEntity|null $address): void
    {
        if (!$address) {
            return;
        }

        $country = $address->getCountry();
        if (!$country) {
            return;
        }

        if ($this->allowedCountries !== null && !\in_array($country->getIso(), $this->allowedCountries, true)) {
            throw new PaymentMethodNotAllowedException('Country is not allowed');
        }

        if ($this->allowedB2bCountries !== null && $address->getCompany() && !\in_array($country->getIso(), $this->allowedB2bCountries, true)) {
            throw new PaymentMethodNotAllowedException('Country is not allowed for B2B');
        }
    }

    private function validateCurrency(?CurrencyEntity $currency): void
    {
        if ($currency && $this->allowedCurrencies !== null && !\in_array($currency->getIsoCode(), $this->allowedCurrencies, true)) {
            throw new PaymentMethodNotAllowedException('Currency is not allowed');
        }
    }

    private function validateMinValue(float $currentValue): void
    {
        if ($currentValue < $this->allowedMinValue) {
            throw new PaymentMethodNotAllowedException('The current cart/order value is lower than the allowed min value');
        }
    }

    private function validateMaxValue(float $currentValue): void
    {
        if ($this->allowedMaxValue !== null && $currentValue > $this->allowedMaxValue) {
            throw new PaymentMethodNotAllowedException('The current cart/order value is higher than the allowed max value');
        }
    }
}
