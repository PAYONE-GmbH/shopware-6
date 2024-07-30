<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

abstract class AbstractPaymentFilterTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    /**
     * @dataProvider dataProviderPaymentHandlerClasses
     */
    public function testItHidesPaymentMethodForNotAllowedCountry(string $paymentHandlerClass): void
    {
        if (!$this->getDisallowedBillingCountry()) {
            static::assertTrue(true);

            return;
        }

        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getDisallowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            null,
            $this->getAllowedCurrency()
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    /**
     * @dataProvider dataProviderPaymentHandlerClasses
     */
    public function testItHidesPaymentMethodForNotAllowedCurrency(string $paymentHandlerClass): void
    {
        if (!$this->getDisallowedCurrency()) {
            static::assertTrue(true);

            return;
        }

        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            null,
            $this->getDisallowedCurrency()
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    /**
     * @dataProvider notAllowedValues
     * @testdox It hides payment method for not allowed value $notAllowedValue on checkout
     */
    public function testItHidesPaymentMethodForNotAllowedValueOnCheckout(float $notAllowedValue, ?string $paymentHandlerClass = null): void
    {
        $paymentHandlerClass ??= $this->getPaymentHandlerClasses()[0];

        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $price = $this->createMock(CartPrice::class);
        $price->method('getTotalPrice')->willReturn($notAllowedValue);

        $cart = $this->createMock(Cart::class);
        $cart->method('getPrice')->willReturn($price);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            null,
            $this->getAllowedCurrency(),
            null,
            $cart
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    /**
     * @dataProvider notAllowedValues
     * @testdox It hides payment method for not allowed value $notAllowedValue on edit order page
     */
    public function testItHidesPaymentMethodForNotAllowedValueOnEditOrderPage(float $notAllowedValue, ?string $paymentHandlerClass = null): void
    {
        $paymentHandlerClass ??= $this->getPaymentHandlerClasses()[0];
        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $price = $this->createMock(CartPrice::class);
        $price->method('getTotalPrice')->willReturn($notAllowedValue);

        $order = $this->createMock(OrderEntity::class);
        $order->method('getPrice')->willReturn($price);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            null,
            $this->getAllowedCurrency(),
            $order
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    /**
     * @dataProvider dataProviderPaymentHandlerClasses
     */
    public function testItNotHidesPaymentMethodForAllowedConditionsOnCheckout(string $paymentHandlerClass): void
    {
        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $price = $this->createMock(CartPrice::class);
        $price->method('getTotalPrice')->willReturn($this->getAllowedValue());

        $cart = $this->createMock(Cart::class);
        $cart->method('getPrice')->willReturn($price);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            $salesChannelContext->getCustomer()->getActiveShippingAddress(),
            $this->getAllowedCurrency(),
            null,
            $cart
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    /**
     * @dataProvider dataProviderPaymentHandlerClasses
     */
    public function testItNotHidesPaymentMethodForAllowedConditionsOnEditOrderPage(string $paymentHandlerClass): void
    {
        $methods = $this->getPaymentMethods($paymentHandlerClass);

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $price = $this->createMock(CartPrice::class);
        $price->method('getTotalPrice')->willReturn($this->getAllowedValue());

        $order = $this->createMock(OrderEntity::class);
        $order->method('getPrice')->willReturn($price);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            $salesChannelContext->getCustomer()->getActiveShippingAddress(),
            $this->getAllowedCurrency(),
            $order
        );

        $this->getFilterService($paymentHandlerClass)->filterPaymentMethods($methods, $filterContext);

        static::assertInPaymentCollection($paymentHandlerClass, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    public function notAllowedValues(): \Generator
    {
        if ($this->getTooLowValue() !== null) {
            yield [
                'value' => $this->getTooLowValue(),
            ];
        }

        if ($this->getTooHighValue() !== null) {
            yield [
                'value' => $this->getTooHighValue(),
            ];
        }

        yield [
            'value' => -1.0,
        ];
    }

    abstract protected function getFilterService(?string $paymentHandlerClass = null): PaymentFilterServiceInterface;

    abstract protected function getDisallowedBillingCountry(): ?string;

    abstract protected function getAllowedBillingCountry(): string;

    abstract protected function getDisallowedCurrency(): ?CurrencyEntity;

    abstract protected function getAllowedCurrency(): CurrencyEntity;

    abstract protected function getTooLowValue(): ?float;

    abstract protected function getTooHighValue(): ?float;

    abstract protected function getAllowedValue(): float;

    /**
     * @return array<<array<class-string>>
     */
    abstract protected function getPaymentHandlerClasses(): array;

    /**
     * @return array<<class-string>
     */
    final protected function dataProviderPaymentHandlerClasses(): array
    {
        return array_map(static fn ($handler) => [$handler], $this->getPaymentHandlerClasses());
    }

    protected function getPaymentMethods(string $paymentHandlerClass): PaymentMethodCollection
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod1->setId(Uuid::randomHex());
        $paymentMethod1->setHandlerIdentifier(PaymentHandlerMock::class);

        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod2->setId(Uuid::randomHex());
        $paymentMethod2->setHandlerIdentifier($paymentHandlerClass);

        return new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
        ]);
    }

    protected static function assertInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods, string $message = ''): void
    {
        static::assertSame(1, $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => $paymentMethod->getHandlerIdentifier() === $paymentHandler
        )->count(), $message);
    }

    protected static function assertNotInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods, string $message = ''): void
    {
        static::assertSame(0, $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => $paymentMethod->getHandlerIdentifier() === $paymentHandler
        )->count(), $message);
    }
}
