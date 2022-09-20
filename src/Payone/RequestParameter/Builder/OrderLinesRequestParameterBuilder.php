<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;

class OrderLinesRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;

    public function __construct(LineItemHydratorInterface $lineItemHydrator)
    {
        $this->lineItemHydrator = $lineItemHydrator;
    }

    /** @param FinancialTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $paymentTransaction   = $arguments->getPaymentTransaction();
        $currency             = $paymentTransaction->getOrder()->getCurrency();
        $requestData          = $arguments->getRequestData();
        $orderLines           = $requestData->get('orderLines', []);
        $isCompleted          = $requestData->get('complete', false);
        $includeShippingCosts = $requestData->get('includeShippingCosts', false);

        if ($currency === null || $paymentTransaction->getOrder()->getLineItems() === null) {
            return [];
        }

        return $this->lineItemHydrator->mapPayoneOrderLinesByRequest(
            $currency,
            $paymentTransaction->getOrder(),
            $orderLines,
            $isCompleted ? true : $includeShippingCosts
        );
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof FinancialTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        switch ($paymentMethod) {
            case PayonePayolutionDebitPaymentHandler::class:
            case PayonePayolutionInstallmentPaymentHandler::class:
            case PayonePayolutionInvoicingPaymentHandler::class:
            case PayoneSecureInvoicePaymentHandler::class:
            case PayoneOpenInvoicePaymentHandler::class:
            case PayoneBancontactPaymentHandler::class:
            case PayoneRatepayDebitPaymentHandler::class:
            case PayoneRatepayInstallmentPaymentHandler::class:
            case PayoneRatepayInvoicingPaymentHandler::class:
                return true;
        }

        return false;
    }
}
