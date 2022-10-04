<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit\AuthorizeRequestParameterBuilder as PayolutionDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AuthorizeRequestParameterBuilder extends PayolutionDebitAuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();

        $parameters = [
            'clearingtype'  => self::CLEARING_TYPE_FINANCING,
            'financingtype' => 'PYV',
            'request'       => self::REQUEST_ACTION_AUTHORIZE,
        ];

        $this->applyBirthdayParameter($parameters, $dataBag);

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA));
    }
}
