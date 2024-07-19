<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * @covers \PayonePayment\Components\PaymentFilter\PayolutionPaymentMethodFilter
 */
class PayolutionPaymentFilterTest extends AbstractPaymentFilterTest
{
    use ConfigurationHelper;

    protected function setUp(): void
    {
        foreach ($this->getPaymentHandlerClasses() as $handlerClass) {
            $this->setPayoneConfig($this->getContainer(), ConfigurationPrefixes::CONFIGURATION_PREFIXES[$handlerClass] . 'CompanyName', 'the-company');
        }
        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', true);
    }

    public function testIfInvoiceGotHiddenOnDisabledB2B(): void
    {
        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', false);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('not-empty');

        $filterContext = new PaymentFilterContext($salesChannelContext, $salesChannelContext->getCustomer()->getActiveBillingAddress());
        $filterService = $this->getFilterService(PayonePayolutionInvoicingPaymentHandler::class);

        $methods = $this->getPaymentMethods(PayonePayolutionInvoicingPaymentHandler::class);
        $filterService->filterPaymentMethods($methods, $filterContext);
        static::assertNotInPaymentCollection(PayonePayolutionInvoicingPaymentHandler::class, $methods, 'unzer invoice should be removed, because B2B is not allowed');
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods, 'the PaymentHandlerMock should be never removed from the available payment-methods');

        // test again, but now the payment method should be available, because we allow B2B
        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', true);
        $methods = $this->getPaymentMethods(PayonePayolutionInvoicingPaymentHandler::class);
        $filterService->filterPaymentMethods($methods, $filterContext);
        static::assertInPaymentCollection(PayonePayolutionInvoicingPaymentHandler::class, $methods, 'after enabling the B2B for invoice, the payment method should be available');
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods, 'the PaymentHandlerMock should be never removed from the available payment-methods');
    }

    /**
     * @dataProvider dataProviderUnzerGotHiddenOnB2B
     */
    public function testIfUnzerGotHiddenOnB2B(string $paymentHandler): void
    {
        // should not take any effect. we enable it to make sure that this configuration got not applied on the other user-payment methods
        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', true);

        $methods = $this->getPaymentMethods($paymentHandler);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('not-empty');

        $filterContext = new PaymentFilterContext($salesChannelContext, $salesChannelContext->getCustomer()->getActiveBillingAddress());
        $filterService = $this->getFilterService($paymentHandler);

        $filterService->filterPaymentMethods($methods, $filterContext);
        static::assertNotInPaymentCollection($paymentHandler, $methods, $paymentHandler . ' should be removed, because B2B is not allowed');
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods, 'the PaymentHandlerMock should be never removed from the available payment-methods');
    }

    public static function dataProviderUnzerGotHiddenOnB2B(): array
    {
        return [
            [PayonePayolutionDebitPaymentHandler::class],
            [PayonePayolutionInstallmentPaymentHandler::class],
        ];
    }

    /**
     * @dataProvider dataProviderUnzerIsAlwaysAvailableForOnB2C
     */
    public function testIfUnzerIsAlwaysAvailableForOnB2C(string $paymentHandler): void
    {
        $methods = $this->getPaymentMethods($paymentHandler);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->assign([
            'company' => null, // shopware does not allow setting null with setCompany().
        ]);

        $filterContext = new PaymentFilterContext($salesChannelContext, $salesChannelContext->getCustomer()->getActiveBillingAddress());
        $filterService = $this->getFilterService($paymentHandler);

        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', false);
        $filterService->filterPaymentMethods($methods, $filterContext);
        static::assertInPaymentCollection($paymentHandler, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);

        $this->setPayoneConfig($this->getContainer(), 'payolutionInvoicingTransferCompanyData', true);
        $filterService->filterPaymentMethods($methods, $filterContext);
        static::assertInPaymentCollection($paymentHandler, $methods);
        static::assertInPaymentCollection(PaymentHandlerMock::class, $methods);
    }

    public static function dataProviderUnzerIsAlwaysAvailableForOnB2C(): array
    {
        return [
            [PayonePayolutionInvoicingPaymentHandler::class],
            [PayonePayolutionDebitPaymentHandler::class],
            [PayonePayolutionInstallmentPaymentHandler::class],
        ];
    }

    protected function getFilterService(?string $paymentHandlerClass = null): PaymentFilterServiceInterface
    {
        $serviceId = match ($paymentHandlerClass) {
            PayonePayolutionInvoicingPaymentHandler::class => 'payone.payment_filter_method.payolution.invoice',
            PayonePayolutionDebitPaymentHandler::class => 'payone.payment_filter_method.payolution.debit',
            PayonePayolutionInstallmentPaymentHandler::class => 'payone.payment_filter_method.payolution.installment',
        };

        if (!$serviceId) {
            throw new \RuntimeException('unknown payment-handler: ' . $paymentHandlerClass);
        }

        return $this->getContainer()->get($serviceId);
    }

    protected function getDisallowedBillingCountry(): ?string
    {
        return null;
    }

    protected function getAllowedBillingCountry(): string
    {
        return 'DE';
    }

    protected function getDisallowedCurrency(): ?CurrencyEntity
    {
        return null;
    }

    protected function getAllowedCurrency(): CurrencyEntity
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $currency->method('getIsoCode')->willReturn('EUR');

        return $currency;
    }

    protected function getTooLowValue(): ?float
    {
        return null;
    }

    protected function getTooHighValue(): ?float
    {
        return null;
    }

    protected function getAllowedValue(): float
    {
        return 100.0;
    }

    protected function getPaymentHandlerClasses(): array
    {
        return [
            PayonePayolutionInvoicingPaymentHandler::class,
            PayonePayolutionDebitPaymentHandler::class,
            PayonePayolutionInstallmentPaymentHandler::class,
        ];
    }
}
