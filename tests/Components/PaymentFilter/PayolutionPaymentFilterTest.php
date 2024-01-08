<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\TestCaseBase\ConfigurationHelper;
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
