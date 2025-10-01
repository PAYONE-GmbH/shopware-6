<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Provider;

/**
 * @deprecated: Will be removed when Capture, Mandate and Refund RequestParameterBuilder are migrated
 *
 * TODO: Remove when Capture, Mandate and Refund RequestParameterBuilder are migrated
 */
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

        if (
            [] === $orderLines
            && $arguments instanceof PaymentTransactionStruct && $this->isAuthorizeAction($arguments)
        ) {
            $parameters = $this->serviceAccessor->lineItemHydrator->mapOrderLines(
                $currency,
                $paymentTransaction->getOrder(),
                $arguments->getSalesChannelContext()->getContext(),
            );
        } else {
            $parameters = $this->serviceAccessor->lineItemHydrator->mapPayoneOrderLinesByRequest(
                $currency,
                $paymentTransaction->getOrder(),
                $orderLines,
                $isCompleted ? true : $includeShippingCosts,
            );
        }

        // For specific payment methods the "pr" parameter must be negative on refunds
        $paymentMethodsThatRequireNegativePriceForRefunds = [
            Provider\Payone\PaymentHandler\SecuredInvoicePaymentHandler::class,
            Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler::class,
            Provider\Payone\PaymentHandler\SecuredDirectDebitPaymentHandler::class,
        ];
        if ($arguments->getAction() === RequestActionEnum::REFUND->value
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
        if ($arguments instanceof FinancialTransactionStruct) {
            switch ($arguments->getPaymentMethod()) {
                case Provider\Payolution\PaymentHandler\DebitPaymentHandler::class:
                case Provider\Payolution\PaymentHandler\InstallmentPaymentHandler::class:
                case Provider\Payolution\PaymentHandler\InvoicePaymentHandler::class:
                case Provider\Payone\PaymentHandler\SecureInvoicePaymentHandler::class:
                case Provider\Payone\PaymentHandler\OpenInvoicePaymentHandler::class:
                case Provider\Bancontact\PaymentHandler\StandardPaymentHandler::class:
                case Provider\Ratepay\PaymentHandler\DebitPaymentHandler::class:
                case Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler::class:
                case Provider\Ratepay\PaymentHandler\InvoicePaymentHandler::class:
                case Provider\Przelewy24\PaymentHandler\StandardPaymentHandler::class:
                case Provider\WeChatPay\PaymentHandler\StandardPaymentHandler::class:
                case Provider\Alipay\PaymentHandler\StandardPaymentHandler::class:
                case Provider\Payone\PaymentHandler\SecuredInvoicePaymentHandler::class:
                case Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler::class:
                case Provider\Payone\PaymentHandler\SecuredDirectDebitPaymentHandler::class:
                case Provider\PostFinance\PaymentHandler\CardPaymentHandler::class:
                case Provider\PostFinance\PaymentHandler\WalletPaymentHandler::class:
                    return true;
            }
        }

        return false;
    }

    private function isAuthorizeAction(AbstractRequestParameterStruct $arguments): bool
    {
        return \in_array(
            $arguments->getAction(),
            [ RequestActionEnum::AUTHORIZE->value, RequestActionEnum::PREAUTHORIZE->value ],
            true
        );
    }
}
