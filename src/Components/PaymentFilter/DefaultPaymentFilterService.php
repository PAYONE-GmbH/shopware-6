<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Country\CountryEntity;
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

    private ?array $allowedCountriesForCurrencies;

    /**
     * @param class-string<\PayonePayment\PaymentHandler\AbstractPayonePaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        string $paymentHandlerClass,
        ?array $allowedCountries = null,
        ?array $allowedB2bCountries = null,
        ?array $allowedCurrencies = null,
        ?array $allowedCountriesForCurrencies = null
    )
    {
        $this->paymentHandlerClass = $paymentHandlerClass;
        $this->allowedCountries = $allowedCountries;
        $this->allowedB2bCountries = $allowedB2bCountries;
        $this->allowedCurrencies = $allowedCurrencies;
        $this->allowedCountriesForCurrencies = $allowedCountriesForCurrencies;
    }

    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext
    ): PaymentMethodCollection
    {
        $currency = $filterContext->getCurrency();
        $billingAddress = $filterContext->getBillingAddress();

        try {
            $this->validateCurrency($currency);
            $this->validateCurrencyForCountry($filterContext);
            $this->validateAddress($billingAddress);
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
     * @throws PaymentMethodNotAllowedException
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

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateCurrency(?CurrencyEntity $currency): void
    {
        if ($currency && $this->allowedCurrencies !== null && !\in_array($currency->getIsoCode(), $this->allowedCurrencies, true)) {
            throw new PaymentMethodNotAllowedException('Currency is not allowed');
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateCurrencyForCountry(PaymentFilterContext $context): void
    {
        if ($this->allowedCountriesForCurrencies === null) {
            return;
        }

        $billingAddress = $context->getBillingAddress();
        $country = $billingAddress ? $billingAddress->getCountry() : null;
        $currency = $context->getCurrency();

        if ($country === null) {
            // should never occur
            throw new PaymentMethodNotAllowedException('Billing address does not have a country. Country is required for this payment method.');
        }

        if ($currency === null) {
            // should never occur
            throw new PaymentMethodNotAllowedException('Currency is not given. Currency is required for this payment method.');
        }

        $countryCode = $country->getIso();
        $currencyCode = $currency->getIsoCode();

        if (!isset($this->allowedCountriesForCurrencies[$currencyCode]) || !\in_array($countryCode, $this->allowedCountriesForCurrencies[$currencyCode])) {
            throw new PaymentMethodNotAllowedException(sprintf('Currency %s is not allowed for country %s', $currencyCode, $countryCode));
        }
    }
}
