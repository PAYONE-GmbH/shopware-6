<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;

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

        $result = $this->getFilterService()->filterPaymentMethods(
            $methods,
            $this->getAllowedCurrency(),
            $salesChannelContext->getCustomer()->getActiveBillingAddress()
        );

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

        $result = $this->getFilterService()->filterPaymentMethods(
            $methods,
            $this->getDisallowedCurrency(),
            $salesChannelContext->getCustomer()->getActiveBillingAddress()
        );

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    public function testItNotHidesPaymentMethodForAllowedCountryAndCurrency(): void
    {
        $methods = $this->getPaymentMethods();

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $result = $this->getFilterService()->filterPaymentMethods(
            $methods,
            $this->getAllowedCurrency(),
            $salesChannelContext->getCustomer()->getActiveBillingAddress()
        );

        static::assertInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    abstract protected function getFilterService(): PaymentFilterServiceInterface;

    abstract protected function getDisallowedBillingCountry(): string;

    abstract protected function getAllowedBillingCountry(): string;

    abstract protected function getDisallowedCurrency(): string;

    abstract protected function getAllowedCurrency(): string;

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
