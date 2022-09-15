<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;


use PayonePayment\PaymentHandler\PayoneKlarnaInstalmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;

trait FinancingTypeTrait
{
    public function getFinancingType(string $paymentMethodHandler): string
    {
        switch ($paymentMethodHandler) {
            case PayoneKlarnaInvoicePaymentHandler::class:
                return 'KIV';
            case PayoneKlarnaInstalmentPaymentHandler::class:
                return 'KIS';
            case PayoneKlarnaDirectDebitPaymentHandler::class:
                return 'KDD';
            default:
                throw new \RuntimeException('invalid payment method'); // TODO use shopware exception
        }
    }
}
