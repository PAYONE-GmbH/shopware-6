<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\PaymentMethod\PayoneAmazonPayExpress;
use PayonePayment\PaymentMethod\PayonePaypalExpress;

interface PaymentHandlerGroups
{
    public const RATEPAY = [
        PayoneRatepayDebitPaymentHandler::class,
        PayoneRatepayInstallmentPaymentHandler::class,
        PayoneRatepayInvoicingPaymentHandler::class,
    ];

    public const BNPL = [
        PayoneSecuredDirectDebitPaymentHandler::class,
        PayoneSecuredInvoicePaymentHandler::class,
        PayoneSecuredInstallmentPaymentHandler::class,
    ];

    public const POSTFINANCE = [
        PayonePostfinanceCardPaymentHandler::class,
        PayonePostfinanceWalletPaymentHandler::class,
    ];

    public const GENERIC_EXPRESS = [
        PayonePaypalExpress::UUID => PayonePaypalExpressPaymentHandler::class,
        PayoneAmazonPayExpress::UUID => PayoneAmazonPayExpressPaymentHandler::class,
    ];
}
