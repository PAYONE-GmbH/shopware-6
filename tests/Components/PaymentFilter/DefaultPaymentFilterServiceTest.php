<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

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

        $systemConfigService = $this->createMock(SystemConfigService::class);

        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, ['DE'], null, ['EUR']);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $chfFilterContext);
        static::assertCount(2, $collection->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the currency is not allowed');

        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['DE'], null, ['EUR']);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $chfFilterContext);
        static::assertCount(1, $collection->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the currency is not allowed');

        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, ['DE'], null, ['EUR']);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $euroFilterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the currency is allowed for the first method');

        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['DE'], null, ['EUR']);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $euroFilterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the currency is allowed for the second and the method');

        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['DE'], null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $euroFilterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause no currency filter is provided');
    }

    public function testB2C(): void
    {
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

        $systemConfigService = $this->createMock(SystemConfigService::class);

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, ['FR'], null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(2, $collection->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['FR'], null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(1, $collection->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, ['FR'], null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the country is allowed for the first method');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['FR'], null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the country is allowed for the second and the method');

        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, null, null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause no country filter is provided');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, [], ['FR'], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertNotContainsOnly(\stdClass::class, $collection->getElements(), false, 'payment method stdclass should be removed, because country FR is only allowed for B2B customers and not B2C customers.');
    }

    public function testB2B(): void
    {
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

        $systemConfigService = $this->createMock(SystemConfigService::class);

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, null, ['FR'], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(2, $collection->getElements(), 'first payment method should be removed, cause service should only process first payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('DE');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, null, ['FR'], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(1, $collection->getElements(), 'second and third payment method should be removed, cause service should only process second/third payment method, and the country is not allowed');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, PaymentHandlerMock::class, null, ['FR'], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the country is allowed for the first method');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, null, ['FR'], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause the country is allowed for the second and th method');

        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, null, null, null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertCount(3, $collection->getElements(), 'no payment method should be removed, cause no country filter is provided');

        $billingAddress->getCountry()->setIso('FR');
        $filterService = new DefaultPaymentFilterService($systemConfigService, \stdClass::class, ['FR'], [], null);
        $filterService->filterPaymentMethods($collection = $this->getMethodCollection(), $filterContext);
        static::assertNotContainsOnly(\stdClass::class, $collection->getElements(), false, 'payment method stdclass should be removed, because country FR is only allowed for B2C customers and not B2B customers.');
    }

    public function testDifferentShippingAddress(): void
    {
        $method = new PaymentMethodEntity();
        $method->setUniqueIdentifier(Uuid::randomHex());
        $method->setHandlerIdentifier(PayoneSecuredInvoicePaymentHandler::class);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $differentShippingAddress = new CustomerAddressEntity();
        $differentShippingAddress->setId(Uuid::randomHex());
        $differentShippingAddress->setFirstName('Different Firstname');
        $salesChannelContext->getCustomer()->setActiveShippingAddress($differentShippingAddress);

        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            $salesChannelContext->getCustomer()->getActiveShippingAddress(),
            $currency
        );

        $configKey = ConfigReader::getConfigKeyByPaymentHandler(
            PayoneSecuredInvoicePaymentHandler::class,
            'AllowDifferentShippingAddress'
        );

        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService
            ->expects(static::once())
            ->method('get')
            ->with(
                static::equalTo($configKey),
                static::equalTo($salesChannelContext->getSalesChannelId())
            )
            ->willReturn(null)
        ;

        $filterService = new DefaultPaymentFilterService($systemConfigService, PayoneSecuredInvoicePaymentHandler::class, null, null, null);
        $methodCollection = $this->getMethodCollection();
        $methodCollection->add($method);
        $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $methodCollection->getElements(), 'payment method should be removed, because in case of missing config the default is "false"');

        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService
            ->expects(static::once())
            ->method('get')
            ->with(
                static::equalTo($configKey),
                static::equalTo($salesChannelContext->getSalesChannelId())
            )
            ->willReturn(true)
        ;

        $filterService = new DefaultPaymentFilterService($systemConfigService, PayoneSecuredInvoicePaymentHandler::class, null, null, null);
        $methodCollection = $this->getMethodCollection();
        $methodCollection->add($method);
        $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(4, $methodCollection->getElements(), 'no payment method should be removed, because a different shipping address is allowed');

        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService
            ->expects(static::once())
            ->method('get')
            ->with(
                static::equalTo($configKey),
                static::equalTo($salesChannelContext->getSalesChannelId())
            )
            ->willReturn(false)
        ;

        $filterService = new DefaultPaymentFilterService($systemConfigService, PayoneSecuredInvoicePaymentHandler::class, null, null, null);
        $methodCollection = $this->getMethodCollection();
        $methodCollection->add($method);
        $filterService->filterPaymentMethods($methodCollection, $filterContext);
        static::assertCount(3, $methodCollection->getElements(), 'payment method should be removed, because a different shipping address is not allowed');
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
