<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\ConfigReader\Exception\ConfigurationPrefixMissingException;
use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\ExpressCheckout\ExpressCheckoutPaymentHandlerAwareInterface;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @todo: refactor this class to use a new payment method filter system (payone.payment_method.filter tag / payment_method_filter.xml)
 */
readonly class DefaultPaymentFilterService implements PaymentFilterServiceInterface
{
    /**
     * @param class-string<AbstractPaymentHandler> $paymentHandlerClass
     */
    public function __construct(
        protected SystemConfigService $systemConfigService,
        protected string $paymentHandlerClass,
        protected ConfigReader $configReader,
        protected CartExtensionService $extensionService,
        private array|null $allowedCountries = null,
        private array|null $allowedB2bCountries = null,
        private array|null $allowedCurrencies = null,
        private float $allowedMinValue = 0.0,
        private float|null $allowedMaxValue = null,
    ) {
    }

    #[\Override]
    final public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
        $supportedPaymentMethods = $this->getSupportedPaymentMethods($methodCollection);

        if ([] === $supportedPaymentMethods->getElements()) {
            return;
        }

        if (0 === $filterContext->getCart()?->getLineItems()->count()) {
            // cart do not have any items, so a checkout is not possible.
            // This makes it possible to still have the payment method as "selected", if the cart ist empty.
            return;
        }

        $currency       = $filterContext->getCurrency();
        $billingAddress = $filterContext->getBillingAddress();

        $currentValue = null;

        if ($filterContext->getCart()) {
            $currentValue = $filterContext->getCart()->getPrice()->getTotalPrice();
        } elseif ($filterContext->getOrder()) {
            $currentValue = $filterContext->getOrder()->getPrice()->getTotalPrice();
        }

        // Validate and remove all supported payment methods if necessary
        try {
            if (true !== $filterContext->hasFlag(PaymentFilterContext::FLAG_SKIP_EC_REQUIRED_DATA_VALIDATION)) {
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
            $this->removePaymentMethods(
                $methodCollection,
                $paymentMethodNotAllowedException->getDisallowedPaymentMethodCollection(),
            );
        }
    }

    protected function additionalChecks(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
    }

    private function getSupportedPaymentMethods(
        PaymentMethodCollection $paymentMethodCollection,
    ): PaymentMethodCollection {
        return $paymentMethodCollection->filter(
            fn (PaymentMethodEntity $paymentMethod)
                => $paymentMethod->getHandlerIdentifier() === $this->paymentHandlerClass,
        );
    }

    private function removePaymentMethods(
        PaymentMethodCollection $paymentMethodCollection,
        PaymentMethodCollection|null $itemsToRemove = null,
    ): void {
        $itemsToRemove = $itemsToRemove ?: $this->getSupportedPaymentMethods($paymentMethodCollection);

        foreach ($itemsToRemove->getIds() as $id) {
            $paymentMethodCollection->remove($id);
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateAddress(CustomerAddressEntity|OrderAddressEntity|null $address): void
    {
        if (!$address) {
            return;
        }

        $country = $address->getCountry();

        if (!$country) {
            return;
        }

        if (
            null !== $this->allowedCountries
            && empty($address->getCompany())
            && !\in_array($country->getIso(), $this->allowedCountries, true)
        ) {
            throw new PaymentMethodNotAllowedException('Country is not allowed');
        }

        if (
            null !== $this->allowedB2bCountries
            && !empty($address->getCompany())
            && !\in_array($country->getIso(), $this->allowedB2bCountries, true)
        ) {
            throw new PaymentMethodNotAllowedException('Country is not allowed for B2B');
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateCurrency(CurrencyEntity|null $currency): void
    {
        if (
            $currency
            && null !== $this->allowedCurrencies
            && !\in_array($currency->getIsoCode(), $this->allowedCurrencies, true)
        ) {
            throw new PaymentMethodNotAllowedException('Currency is not allowed');
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateMinValue(float $currentValue): void
    {
        if ($currentValue < $this->allowedMinValue) {
            throw new PaymentMethodNotAllowedException(
                'The current cart/order value is lower than the allowed min value',
            );
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateMaxValue(float $currentValue): void
    {
        if (null !== $this->allowedMaxValue && $currentValue > $this->allowedMaxValue) {
            throw new PaymentMethodNotAllowedException(
                'The current cart/order value is higher than the allowed max value',
            );
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    protected function validateDifferentShippingAddress(
        PaymentMethodCollection $paymentMethods,
        PaymentFilterContext $filterContext,
    ): void {
        // addresses are already identical - it is not required to check if it is allowed if they are identical or not
        if ($filterContext->areAddressesIdentical()) {
            return;
        }

        $disallowedPaymentMethods = [];

        foreach ($paymentMethods as $paymentMethod) {
            try {
                $configKey = $this->configReader->getConfigKeyByPaymentHandler(
                    $paymentMethod->getHandlerIdentifier(),
                    'AllowDifferentShippingAddress',
                );
            } catch (ConfigurationPrefixMissingException) {
                continue;
            }

            /** @var boolean|null $differentShippingAddressAllowed */
            $differentShippingAddressAllowed = $this->systemConfigService->get(
                $configKey,
                $filterContext->getSalesChannelContext()->getSalesChannelId(),
            ) ?? false;

            // if configuration value is null, the payment method should be removed.
            if (false === $differentShippingAddressAllowed) {
                $disallowedPaymentMethods[] = $paymentMethod;
            }
        }

        if ([] !== $disallowedPaymentMethods) {
            throw new PaymentMethodNotAllowedException(
                'It is not permitted to use a different shipping address',
                new PaymentMethodCollection($disallowedPaymentMethods),
            );
        }
    }

    /**
     * @throws PaymentMethodNotAllowedException
     */
    private function validateGenericExpressCheckout(PaymentFilterContext $filterContext): void
    {
        if (!\is_subclass_of($this->paymentHandlerClass, ExpressCheckoutPaymentHandlerAwareInterface::class, true)) {
            // payment handler is not a generic express-checkout
            return;
        }

        $salesChannelContext = $filterContext->getSalesChannelContext();
        $paymentMethod       = $salesChannelContext->getPaymentMethod();

        if ($paymentMethod->getHandlerIdentifier() !== $this->paymentHandlerClass) {
            throw new PaymentMethodNotAllowedException(
                'payment is a generic express-checkout which has not been initialized yet.',
            );
        }

        $extensionData = $filterContext->getCart()?->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if (!$extensionData instanceof CheckoutCartPaymentData || empty($extensionData->getWorkorderId())) {
            throw new PaymentMethodNotAllowedException(
                'payment is a generic express-checkout which has not been initialized yet.',
            );
        }
    }
}
