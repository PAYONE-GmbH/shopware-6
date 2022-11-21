<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\PageLoadedEvent;

class DefaultPaymentFilterService implements PaymentFilterServiceInterface
{
    /**
     * @var class-string<\PayonePayment\PaymentHandler\AbstractPayonePaymentHandler>
     */
    private string $paymentHandlerClass;

    private ?array $allowedCountries;

    private ?array $allowedB2bCountries;

    private ?array $allowedCurrencies;

    /**
     * @param class-string<\PayonePayment\PaymentHandler\AbstractPayonePaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        string $paymentHandlerClass,
        ?array $allowedCountries = null,
        ?array $allowedB2bCountries = null,
        ?array $allowedCurrencies = null
    ) {
        $this->paymentHandlerClass = $paymentHandlerClass;
        $this->allowedCountries = $allowedCountries;
        $this->allowedB2bCountries = $allowedB2bCountries;
        $this->allowedCurrencies = $allowedCurrencies;
    }

    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, string $currencyIso, $billingAddress = null, $shippingAddress = null): PaymentMethodCollection
    {
        if ($this->isCurrencyAllowed($currencyIso) && (!$billingAddress || $this->isAddressAllowed($billingAddress))) {
            return $methodCollection;
        }

        return $this->removePaymentMethod($methodCollection);
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

    public function filterPaymentMethodsAdditionalCheck(PaymentMethodCollection $methodCollection, PageLoadedEvent $event): PaymentMethodCollection
    {
        return $methodCollection;
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
     * @param CustomerAddressEntity|OrderAddressEntity $address
     */
    private function isAddressAllowed($address): bool
    {
        $country = $address->getCountry();

        return $country
            && ($this->allowedCountries === null || \in_array($country->getIso(), $this->allowedCountries, true))
            && (!$address->getCompany() || $this->allowedB2bCountries === null || \in_array($country->getIso(), $this->allowedB2bCountries, true));
    }

    private function isCurrencyAllowed(string $currencyCode): bool
    {
        return $this->allowedCurrencies === null || \in_array($currencyCode, $this->allowedCurrencies, true);
    }
}
