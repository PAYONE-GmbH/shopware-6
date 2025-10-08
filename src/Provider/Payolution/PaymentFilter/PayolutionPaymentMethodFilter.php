<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentFilter;

use PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\Provider\Payolution\PaymentHandler\InvoicePaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class PayolutionPaymentMethodFilter extends DefaultPaymentFilterService
{
    /**
     * @throws PaymentMethodNotAllowedException
     */
    #[\Override]
    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        if (empty($this->getPaymentHandlerConfiguration($filterContext->getSalesChannelContext(), 'CompanyName'))) {
            throw new PaymentMethodNotAllowedException('Unzer: missing configuration: merchant-company name.');
        }

        // TODO: Check refctoring possibilities
        if (!empty($filterContext->getBillingAddress()?->getCompany())) {
            if (InvoicePaymentHandler::class === $this->paymentHandlerClass) {
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
        return $this->getConfiguration(
            $context,
            $this->configReader->getConfigKeyByPaymentHandler($this->paymentHandlerClass, $configKey),
        );
    }

    private function getConfiguration(SalesChannelContext $context, string $configName): mixed
    {
        return $this->systemConfigService->get($configName, $context->getSalesChannel()->getId());
    }
}
