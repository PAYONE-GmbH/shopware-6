<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\CaptureStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class OrderLinesRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;

    public function __construct(LineItemHydratorInterface $lineItemHydrator)
    {
        $this->lineItemHydrator = $lineItemHydrator;
    }

    /** @param FinancialTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction = $arguments->getPaymentTransaction();

        $currency   = $paymentTransaction->getOrder()->getCurrency();
        $orderLines = $arguments->getRequestData()->get('orderLines', []);

        if (empty($orderLines) || empty($currency) || empty($paymentTransaction->getOrder()->getLineItems())) {
            return [];
        }

        return $this->lineItemHydrator->mapPayoneOrderLinesByRequest($currency, $paymentTransaction->getOrder()->getLineItems(), $orderLines);
    }

    public function supports(Struct $arguments): bool
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
                return true;
        }

        return false;
    }
}
