<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Provider;

/**
 * @deprecated Optimize
 */
interface PaymentHandlerGroups
{
    public const RATEPAY = [
        Provider\Ratepay\PaymentHandler\DebitPaymentHandler::class,
        Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler::class,
        Provider\Ratepay\PaymentHandler\InvoicePaymentHandler::class,
    ];

    public const BNPL = [
        Provider\Payone\PaymentHandler\SecuredDirectDebitPaymentHandler::class,
        Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler::class,
        Provider\Payone\PaymentHandler\SecuredInvoicePaymentHandler::class,
    ];

    public const POSTFINANCE = [
        Provider\PostFinance\PaymentHandler\CardPaymentHandler::class,
        Provider\PostFinance\PaymentHandler\WalletPaymentHandler::class,
    ];

    public const GENERIC_EXPRESS = [
        Provider\PayPal\PaymentMethod\ExpressPaymentMethod::UUID    => Provider\PayPal\PaymentHandler\ExpressPaymentHandler::class,
        Provider\PayPal\PaymentMethod\ExpressV2PaymentMethod::UUID  => Provider\PayPal\PaymentHandler\ExpressV2PaymentHandler::class,
        Provider\AmazonPay\PaymentMethod\ExpressPaymentMethod::UUID => Provider\AmazonPay\PaymentHandler\ExpressPaymentHandler::class,
    ];
}
