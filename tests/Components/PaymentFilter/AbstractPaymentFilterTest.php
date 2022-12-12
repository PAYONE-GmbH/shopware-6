<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
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

    public function testItHidesPaymentMethodForNotAllowedCountry(): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    public function testItHidesPaymentMethodForNotAllowedCurrency(): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    /**
     * @dataProvider notAllowedValues
     * @testdox It hides payment method for not allowed value $notAllowedValue on checkout
     */
    public function testItHidesPaymentMethodForNotAllowedValueOnCheckout(float $notAllowedValue): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    /**
     * @dataProvider notAllowedValues
     * @testdox It hides payment method for not allowed value $notAllowedValue on edit order page
     */
    public function testItHidesPaymentMethodForNotAllowedValueOnEditOrderPage(float $notAllowedValue): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    public function testItNotHidesPaymentMethodForAllowedConditionsOnCheckout(): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    public function testItNotHidesPaymentMethodForAllowedConditionsOnEditOrderPage(): void
    {
        $methods = $this->getPaymentMethods();

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

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
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

    abstract protected function getFilterService(): PaymentFilterServiceInterface;

    abstract protected function getDisallowedBillingCountry(): string;

    abstract protected function getAllowedBillingCountry(): string;

    abstract protected function getDisallowedCurrency(): CurrencyEntity;

    abstract protected function getAllowedCurrency(): CurrencyEntity;

    abstract protected function getTooLowValue(): ?float;

    abstract protected function getTooHighValue(): ?float;

    abstract protected function getAllowedValue(): float;

    /**
     * @return class-string
     */
    abstract protected function getPaymentHandlerClass(): string;

    protected function getPaymentMethods(): PaymentMethodCollection
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod1->setId(Uuid::randomHex());
        $paymentMethod1->setHandlerIdentifier(PaymentHandlerMock::class);

        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod2->setId(Uuid::randomHex());
        $paymentMethod2->setHandlerIdentifier($this->getPaymentHandlerClass());

        return new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
        ]);
    }

    protected static function assertInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods): void
    {
        static::assertSame(1, $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return $paymentMethod->getHandlerIdentifier() === $paymentHandler;
            }
        )->count());
    }

    protected static function assertNotInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods): void
    {
        static::assertSame(0, $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return $paymentMethod->getHandlerIdentifier() === $paymentHandler;
            }
        )->count());
    }
}
