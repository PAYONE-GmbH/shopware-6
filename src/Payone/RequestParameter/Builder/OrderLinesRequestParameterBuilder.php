<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\PaymentHandler\AbstractPostfinancePaymentHandler;
use PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class OrderLinesRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param FinancialTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $paymentTransaction = $arguments->getPaymentTransaction();
        $currency = $paymentTransaction->getOrder()->getCurrency();
        $requestData = $arguments->getRequestData();
        $orderLines = $requestData->all('orderLines');
        $isCompleted = $requestData->get('complete', false);
        $includeShippingCosts = $requestData->get('includeShippingCosts', false);

        if ($currency === null || $paymentTransaction->getOrder()->getLineItems() === null) {
            return [];
        }

        if ($arguments instanceof PaymentTransactionStruct && $this->isAuthorizeAction($arguments) && $orderLines === []) {
            $parameters = $this->serviceAccessor->lineItemHydrator->mapOrderLines($currency, $paymentTransaction->getOrder(), $arguments->getSalesChannelContext()->getContext());
        } else {
            $parameters = $this->serviceAccessor->lineItemHydrator->mapPayoneOrderLinesByRequest(
                $currency,
                $paymentTransaction->getOrder(),
                $orderLines,
                $isCompleted ? true : $includeShippingCosts
            );
        }

        // For specific payment methods the "pr" parameter must be negative on refunds
        $paymentMethodsThatRequireNegativePriceForRefunds = [
            PayoneSecuredInvoicePaymentHandler::class,
            PayoneSecuredInstallmentPaymentHandler::class,
            PayoneSecuredDirectDebitPaymentHandler::class,
        ];
        if ($arguments->getAction() === self::REQUEST_ACTION_REFUND
            && \in_array($arguments->getPaymentMethod(), $paymentMethodsThatRequireNegativePriceForRefunds, true)) {
            foreach ($parameters as $key => &$parameter) {
                if (str_starts_with($key, 'pr[')) {
                    $parameter *= -1;
                }
            }
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if ($arguments instanceof PaymentTransactionStruct) {
            $config = $this->serviceAccessor->configReader->read($arguments->getPaymentTransaction()->getOrder()->getSalesChannelId());

            if ($this->isAuthorizeAction($arguments) && $config->get('submitOrderLineItems', false)) {
                return true;
            }

            // for some methods the line items are always required
            if (is_subclass_of($arguments->getPaymentMethod(), AbstractKlarnaPaymentHandler::class)) {
                return true;
            }

            // for some methods the line items are always required
            switch ($arguments->getPaymentMethod()) {
                case PayoneOpenInvoicePaymentHandler::class:
                case PayoneRatepayDebitPaymentHandler::class:
                case PayoneRatepayInstallmentPaymentHandler::class:
                case PayoneRatepayInvoicingPaymentHandler::class:
                case PayoneSecuredDirectDebitPaymentHandler::class:
                case PayoneSecuredInstallmentPaymentHandler::class:
                case PayoneSecuredInvoicePaymentHandler::class:
                case PayoneSecureInvoicePaymentHandler::class:
                case PayonePayolutionDebitPaymentHandler::class:
                case PayonePayolutionInstallmentPaymentHandler::class:
                case PayonePayolutionInvoicingPaymentHandler::class:
                    return true;
            }
        }

        if ($arguments instanceof FinancialTransactionStruct) {
            switch ($arguments->getPaymentMethod()) {
                case PayonePayolutionDebitPaymentHandler::class:
                case PayonePayolutionInstallmentPaymentHandler::class:
                case PayonePayolutionInvoicingPaymentHandler::class:
                case PayoneSecureInvoicePaymentHandler::class:
                case PayoneOpenInvoicePaymentHandler::class:
                case PayoneBancontactPaymentHandler::class:
                case PayoneRatepayDebitPaymentHandler::class:
                case PayoneRatepayInstallmentPaymentHandler::class:
                case PayoneRatepayInvoicingPaymentHandler::class:
                case PayonePrzelewy24PaymentHandler::class:
                case PayoneWeChatPayPaymentHandler::class:
                case PayoneAlipayPaymentHandler::class:
                case PayoneSecuredInvoicePaymentHandler::class:
                case PayoneSecuredInstallmentPaymentHandler::class:
                case PayoneSecuredDirectDebitPaymentHandler::class:
                    return true;
            }

            if (is_subclass_of($arguments->getPaymentMethod(), AbstractPostfinancePaymentHandler::class)) {
                return true;
            }
        }

        return false;
    }

    private function isAuthorizeAction(AbstractRequestParameterStruct $arguments): bool
    {
        return \in_array($arguments->getAction(), [self::REQUEST_ACTION_AUTHORIZE, self::REQUEST_ACTION_PREAUTHORIZE], true);
    }
}
