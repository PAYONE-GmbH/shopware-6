<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\RequestConstants;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PreCheckRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    /**
     * @param PayolutionAdditionalActionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $currency = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart = $arguments->getCart();

        $parameters = [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Invoicing',
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => 'PYV',
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
            'workorderid' => $arguments->getWorkorderId(),
        ];

        if (!empty($dataBag->get(RequestConstants::BIRTHDAY))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get(RequestConstants::BIRTHDAY));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($parameters, $salesChannelContext);
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PayolutionAdditionalActionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_PAYOLUTION_PRE_CHECK;
    }

    protected function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA));
    }

    protected function provideCompanyParams(array &$parameters, SalesChannelContext $salesChannelContext): void
    {
        $customer = $salesChannelContext->getCustomer();

        if ($customer === null) {
            return;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return;
        }

        if ($billingAddress->getCompany() || $customer->getCompany()) {
            $parameters['add_paydata[b2b]'] = 'yes';

            $vatIds = $customer->getVatIds();

            if (\is_array($vatIds) && isset($vatIds[0])) {
                $parameters['add_paydata[company_uid]'] = $vatIds[0];
            }
        }
    }
}
