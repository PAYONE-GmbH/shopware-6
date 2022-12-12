<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

class DefaultPaymentFilterService implements PaymentFilterServiceInterface
{
    /**
     * @var class-string<\PayonePayment\PaymentHandler\AbstractPayonePaymentHandler>
     */
    private string $paymentHandlerClass;

    private ?array $allowedCountries;

    private ?array $allowedB2bCountries;

    private ?array $allowedCurrencies;

    private float $allowedMinValue;

    private ?float $allowedMaxValue;

    /**
     * @param class-string<\PayonePayment\PaymentHandler\AbstractPayonePaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        string $paymentHandlerClass,
        ?array $allowedCountries = null,
        ?array $allowedB2bCountries = null,
        ?array $allowedCurrencies = null,
        float $allowedMinValue = 0.0,
        ?float $allowedMaxValue = null
    ) {
        $this->paymentHandlerClass = $paymentHandlerClass;
        $this->allowedCountries = $allowedCountries;
        $this->allowedB2bCountries = $allowedB2bCountries;
        $this->allowedCurrencies = $allowedCurrencies;
        $this->allowedMinValue = $allowedMinValue;
        $this->allowedMaxValue = $allowedMaxValue;
    }

    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext
    ): PaymentMethodCollection {
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
        } catch (PaymentMethodNotAllowedException $e) {
            $methodCollection = $this->removePaymentMethod($methodCollection);
        }

        return $methodCollection;
    }

    /**
     * returns true, if the method should be filtered out.
     *
     * @internal method needs to be public, so it can be called by `removePaymentMethod`
     */
    public function canMethodRemoved(PaymentMethodEntity $paymentMethod): bool
    {
        $refClass = new \ReflectionClass($this->paymentHandlerClass);

        return $refClass->isAbstract()
            ? is_subclass_of($paymentMethod->getHandlerIdentifier(), $this->paymentHandlerClass)
            : $paymentMethod->getHandlerIdentifier() === $this->paymentHandlerClass;
    }

    protected function removePaymentMethod(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        $that = $this;
        // filter-method needs a closure (forced anonymous function) so we can not use [$this, 'filterMethod']
        return $paymentMethodCollection->filter(static function (PaymentMethodEntity $entity) use ($that) {
            return !$that->canMethodRemoved($entity);
        });
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity|null $address
     */
    private function validateAddress($address): void
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

        if ($address->getCompany() && $this->allowedB2bCountries !== null && !\in_array($country->getIso(), $this->allowedB2bCountries, true)) {
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
