<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\ConfigReader\Exception\ConfigurationPrefixMissingException;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class DefaultPaymentFilterService implements PaymentFilterServiceInterface
{
    /**
     * @param class-string<AbstractPayonePaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        protected readonly SystemConfigService $systemConfigService,
        protected readonly string $paymentHandlerClass,
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
    ): void {
        $supportedPaymentMethods = $this->getSupportedPaymentMethods($methodCollection);
        if ($supportedPaymentMethods->getElements() === []) {
            return;
        }

        if ($filterContext->getCart()?->getLineItems()->count() === 0) {
            // cart do not have any items, so a checkout is not possible.
            // This makes it possible to still have the payment method as "selected", if the cart ist empty.
            return;
        }

        $currency = $filterContext->getCurrency();
        $billingAddress = $filterContext->getBillingAddress();

        $currentValue = null;
        if ($filterContext->getCart()) {
            $currentValue = $filterContext->getCart()->getPrice()->getTotalPrice();
        } elseif ($filterContext->getOrder()) {
            $currentValue = $filterContext->getOrder()->getPrice()->getTotalPrice();
        }

        // Validate and remove all supported payment methods if necessary
        try {
            if ($filterContext->hasFlag(PaymentFilterContext::FLAG_SKIP_EC_REQUIRED_DATA_VALIDATION) !== true) {
                $this->validateGenericExpressCheckout($filterContext);
            }
            $this->validateCurrency($currency);
            $this->validateAddress($billingAddress);

            if ($currentValue) {
                $this->validateMinValue($currentValue);
                $this->validateMaxValue($currentValue);
            }

            // Validate and remove a specific payment method if necessary
            $this->validateDifferentShippingAddress($supportedPaymentMethods, $filterContext);

            $this->additionalChecks($methodCollection, $filterContext);
        } catch (PaymentMethodNotAllowedException $paymentMethodNotAllowedException) {
            $this->removePaymentMethods($methodCollection, $paymentMethodNotAllowedException->getDisallowedPaymentMethodCollection());
        }
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

    private function removePaymentMethods(PaymentMethodCollection $paymentMethodCollection, ?PaymentMethodCollection $itemsToRemove = null): void
    {
        $itemsToRemove = $itemsToRemove ?: $this->getSupportedPaymentMethods($paymentMethodCollection);

        foreach ($itemsToRemove->getIds() as $id) {
            $paymentMethodCollection->remove($id);
        }
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

        if ($this->allowedCountries !== null && empty($address->getCompany()) && !\in_array($country->getIso(), $this->allowedCountries, true)) {
            throw new PaymentMethodNotAllowedException('Country is not allowed');
        }

        if ($this->allowedB2bCountries !== null && !empty($address->getCompany()) && !\in_array($country->getIso(), $this->allowedB2bCountries, true)) {
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

    private function validateDifferentShippingAddress(PaymentMethodCollection $paymentMethods, PaymentFilterContext $filterContext): void
    {
        // addresses are already identical - it is not required to check if it is allowed if they are identical or not
        if ($filterContext->areAddressesIdentical()) {
            return;
        }

        $disallowedPaymentMethods = [];

        foreach ($paymentMethods as $paymentMethod) {
            try {
                $configKey = ConfigReader::getConfigKeyByPaymentHandler(
                    $paymentMethod->getHandlerIdentifier(),
                    'AllowDifferentShippingAddress'
                );
            } catch (ConfigurationPrefixMissingException) {
                continue;
            }

            /** @var boolean|null $differentShippingAddressAllowed */
            $differentShippingAddressAllowed = $this->systemConfigService->get(
                $configKey,
                $filterContext->getSalesChannelContext()->getSalesChannelId()
            ) ?? false;

            // if configuration value is null, the payment method should be removed.
            if ($differentShippingAddressAllowed === false) {
                $disallowedPaymentMethods[] = $paymentMethod;
            }
        }

        if ($disallowedPaymentMethods !== []) {
            throw new PaymentMethodNotAllowedException(
                'It is not permitted to use a different shipping address',
                new PaymentMethodCollection($disallowedPaymentMethods)
            );
        }
    }

    private function validateGenericExpressCheckout(PaymentFilterContext $filterContext): void
    {
        if (\in_array($this->paymentHandlerClass, PaymentHandlerGroups::GENERIC_EXPRESS, true) === false) {
            // payment handler is not a generic express-checkout
            return;
        }

        if ($filterContext->getSalesChannelContext()->getPaymentMethod()->getHandlerIdentifier() === $this->paymentHandlerClass) {
            $extensionData = $filterContext->getCart()?->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

            if (!$extensionData instanceof CheckoutCartPaymentData || empty($extensionData->getWorkorderId())) {
                throw new PaymentMethodNotAllowedException('payment is a generic express-checkout which has not been initialized yet.');
            }
        } else {
            throw new PaymentMethodNotAllowedException('payment is a generic express-checkout which has not been initialized yet.');
        }
    }
}
