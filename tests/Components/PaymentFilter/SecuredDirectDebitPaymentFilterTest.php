<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\Components\PaymentFilter\PaymentFilterServiceInterface;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\PayoneBNPLPaymentMethodFilter
 */
class SecuredDirectDebitPaymentFilterTest extends AbstractPaymentFilterTest
{
    public function testItHidesPaymentMethodForDifferentShippingAddressOnCheckout(): void
    {
        $methods = $this->getPaymentMethods();

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);

        $differentShippingAddress = new CustomerAddressEntity();
        $differentShippingAddress->setFirstName('Different Firstname');
        $salesChannelContext->getCustomer()->setActiveShippingAddress($differentShippingAddress);

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            $salesChannelContext->getCustomer()->getActiveShippingAddress(),
            $this->getAllowedCurrency()
        );

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    public function testItHidesPaymentMethodForDifferentShippingAddressOnEditOrderPage(): void
    {
        $methods = $this->getPaymentMethods();

        $country = new CountryEntity();
        $country->setIso($this->getAllowedBillingCountry());

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $billingAddress = new OrderAddressEntity();
        $billingAddress->setCountry($country);
        $billingAddress->setFirstName('Foo');

        $shippingAddress = new OrderAddressEntity();
        $shippingAddress->setCountry($country);
        $shippingAddress->setFirstName('Bar');

        $filterContext = new PaymentFilterContext(
            $salesChannelContext,
            $billingAddress,
            $shippingAddress,
            $this->getAllowedCurrency()
        );

        $result = $this->getFilterService()->filterPaymentMethods($methods, $filterContext);

        static::assertNotInPaymentCollection($this->getPaymentHandlerClass(), $result);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $result);
    }

    protected function getFilterService(): PaymentFilterServiceInterface
    {
        return $this->getContainer()->get('payone.payment_filter_method.secured_direct_debit');
    }

    protected function getDisallowedBillingCountry(): string
    {
        return 'CZ';
    }

    protected function getAllowedBillingCountry(): string
    {
        return 'DE';
    }

    protected function getDisallowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('CZK');

        return $currency;
    }

    protected function getAllowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        return $currency;
    }

    protected function getTooLowValue(): ?float
    {
        return 1.0;
    }

    protected function getTooHighValue(): ?float
    {
        return 2000.0;
    }

    protected function getAllowedValue(): float
    {
        return 100.0;
    }

    protected function getPaymentHandlerClass(): string
    {
        return PayoneSecuredDirectDebitPaymentHandler::class;
    }
}
