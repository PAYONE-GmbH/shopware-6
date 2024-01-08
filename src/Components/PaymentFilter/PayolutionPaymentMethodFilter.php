<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionPaymentMethodFilter extends DefaultPaymentFilterService
{
    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        if (empty($this->getPaymentHandlerConfiguration($filterContext->getSalesChannelContext(), 'CompanyName'))) {
            throw new PaymentMethodNotAllowedException('Payolution: missing merchant-company name.');
        }

        if ($this->paymentHandlerClass === PayonePayolutionInvoicingPaymentHandler::class
            && !($this->getPaymentHandlerConfiguration($filterContext->getSalesChannelContext(), 'TransferCompanyData'))
        ) {
            throw new PaymentMethodNotAllowedException('Payolution Invoicing: Missing configuration.');
        }
    }

    private function getPaymentHandlerConfiguration(SalesChannelContext $context, string $configKey): mixed
    {
        return $this->getConfiguration($context, ConfigReader::getConfigKeyByPaymentHandler($this->paymentHandlerClass, $configKey));
    }

    private function getConfiguration(SalesChannelContext $context, string $configName): mixed
    {
        return $this->systemConfigService->get($configName, $context->getSalesChannel()->getId());
    }
}
