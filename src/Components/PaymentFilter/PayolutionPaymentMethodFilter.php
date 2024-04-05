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
            throw new PaymentMethodNotAllowedException('Unzer: missing configuration: merchant-company name.');
        }

        if (!empty($filterContext->getBillingAddress()?->getCompany())) {
            if ($this->paymentHandlerClass === PayonePayolutionInvoicingPaymentHandler::class) {
                if (!($this->getPaymentHandlerConfiguration($filterContext->getSalesChannelContext(), 'TransferCompanyData'))) {
                    throw new PaymentMethodNotAllowedException('Unzer Invoice: B2B is not allowed by configuration.');
                }
            } else {
                throw new PaymentMethodNotAllowedException('Unzer: B2B is only allowed for invoice (if enabled).');
            }
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
