<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService
 */
class DefaultPaymentFilterServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testCurrency(): void
    {
        $methodCollection = $this->getMethodCollection();

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $billingAddress = $salesChannelContext->getCustomer()->getActiveBillingAddress();
        $shippingAddress = $salesChannelContext->getCustomer()->getActiveShippingAddress();

        $billingAddress->getCountry()->setIso('DE');

        $chfCurrency = $this->createMock(CurrencyEntity::class);
        $chfCurrency->method('getIsoCode')->willReturn('CHF');

        $euroCurrency = $this->createMock(CurrencyEntity::class);
        $euroCurrency->method('getIsoCode')->willReturn('EUR');

        $chfFilterContext = new PaymentFilterContext(
            $salesChannelContext,
            $billingAddress,
            $shippingAddress,
            $chfCurrency
        );

        $euroFilterContext = new PaymentFilterContext(
            $salesChannelContext,
            $billingAddress,
            $shippingAddress,
            $euroCurrency
        );

        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, ['DE'], null, ['EUR']);
        $result = $filterService->filterPaymentMethods($methodCollection, $chfFilterContext);
        static::assertCount(2, $result->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the currency is not allowed');

        $filterService = new DefaultPaymentFilterService(\stdClass::class, ['DE'], null, ['EUR']);
        $result = $filterService->filterPaymentMethods($methodCollection, $chfFilterContext);
        static::assertCount(1, $result->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the currency is not allowed');

        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, ['DE'], null, ['EUR']);
        $result = $filterService->filterPaymentMethods($methodCollection, $euroFilterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the currency is allowed for the first method');

        $filterService = new DefaultPaymentFilterService(\stdClass::class, ['DE'], null, ['EUR']);
        $result = $filterService->filterPaymentMethods($methodCollection, $euroFilterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the currency is allowed for the second and the method');

        $filterService = new DefaultPaymentFilterService(\stdClass::class, ['DE'], null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $euroFilterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause no currency filter is provided');
    }

    public function testB2C(): void
    {
        $methodCollection = $this->getMethodCollection();

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $billingAddress = $salesChannelContext->getCustomer()->getActiveBillingAddress();
        $shippingAddress = $salesChannelContext->getCustomer()->getActiveShippingAddress();

        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $billingAddress,
            $shippingAddress,
            $currency
        );

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, ['FR'], null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(2, $result->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService(\stdClass::class, ['FR'], null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(1, $result->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, ['FR'], null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the country is allowed for the first method');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService(\stdClass::class, ['FR'], null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the country is allowed for the second and the method');

        $filterService = new DefaultPaymentFilterService(\stdClass::class, null, null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause no country filter is provided');
    }

    public function testB2B(): void
    {
        $methodCollection = $this->getMethodCollection();

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $billingAddress = $salesChannelContext->getCustomer()->getActiveBillingAddress();
        $shippingAddress = $salesChannelContext->getCustomer()->getActiveShippingAddress();
        $billingAddress->setCompany('Test company');

        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $billingAddress,
            $shippingAddress,
            $currency
        );

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, null, ['FR'], null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(2, $result->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService(\stdClass::class, null, ['FR'], null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(1, $result->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService(PaymentHandlerMock::class, null, ['FR'], null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the country is allowed for the first method');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService(\stdClass::class, null, ['FR'], null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause the country is allowed for the second and th method');

        $filterService = new DefaultPaymentFilterService(\stdClass::class, null, null, null);
        $result = $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $result->getElements(), 'no payment method should be removed, cause no country filter is provided');
    }

    private function getMethodCollection(): PaymentMethodCollection
    {
        $method1 = new PaymentMethodEntity();
        $method1->setUniqueIdentifier(Uuid::randomHex());
        $method1->setHandlerIdentifier(PaymentHandlerMock::class);

        $method2 = new PaymentMethodEntity();
        $method2->setUniqueIdentifier(Uuid::randomHex());
        $method2->setHandlerIdentifier(\stdClass::class);

        $method3 = new PaymentMethodEntity();
        $method3->setUniqueIdentifier(Uuid::randomHex());
        $method3->setHandlerIdentifier(\stdClass::class);

        return new PaymentMethodCollection([$method1, $method2, $method3]);
    }
}
