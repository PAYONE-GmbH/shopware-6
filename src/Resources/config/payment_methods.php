<?php

declare(strict_types=1);

use PayonePayment\Provider;

return [
    Provider\Alipay\PaymentMethod\StandardPaymentMethod::class,
    Provider\AmazonPay\PaymentMethod\ExpressPaymentMethod::class,
    Provider\AmazonPay\PaymentMethod\StandardPaymentMethod::class,
    Provider\ApplePay\PaymentMethod\StandardPaymentMethod::class,
    Provider\Bancontact\PaymentMethod\StandardPaymentMethod::class,
    Provider\Eps\PaymentMethod\StandardPaymentMethod::class,
    Provider\IDeal\PaymentMethod\StandardPaymentMethod::class,
    Provider\Klarna\PaymentMethod\DirectDebitPaymentMethod::class,
    Provider\Klarna\PaymentMethod\InstallmentPaymentMethod::class,
    Provider\Klarna\PaymentMethod\InvoicePaymentMethod::class,
    Provider\Paydirekt\PaymentMethod\StandardPaymentMethod::class,
    Provider\Payolution\PaymentMethod\DebitPaymentMethod::class,
    Provider\Payolution\PaymentMethod\InstallmentPaymentMethod::class,
    Provider\Payolution\PaymentMethod\InvoicePaymentMethod::class,
    Provider\Payone\PaymentMethod\CreditCardPaymentMethod::class,
    Provider\Payone\PaymentMethod\DebitPaymentMethod::class,
    Provider\Payone\PaymentMethod\OpenInvoicePaymentMethod::class,
    Provider\Payone\PaymentMethod\PrepaymentPaymentMethod::class,
    Provider\Payone\PaymentMethod\SecuredInvoicePaymentMethod::class,
    Provider\Payone\PaymentMethod\SecuredInstallmentPaymentMethod::class,
    Provider\Payone\PaymentMethod\SecuredDirectDebitPaymentMethod::class,
    Provider\Payone\PaymentMethod\SecureInvoicePaymentMethod::class,
    Provider\PayPal\PaymentMethod\ExpressPaymentMethod::class,
    Provider\PayPal\PaymentMethod\ExpressV2PaymentMethod::class,
    Provider\PayPal\PaymentMethod\StandardPaymentMethod::class,
    Provider\PayPal\PaymentMethod\StandardV2PaymentMethod::class,
    Provider\PostFinance\PaymentMethod\CardPaymentMethod::class,
    Provider\PostFinance\PaymentMethod\WalletPaymentMethod::class,
    Provider\Przelewy24\PaymentMethod\StandardPaymentMethod::class,
    Provider\Ratepay\PaymentMethod\DebitPaymentMethod::class,
    Provider\Ratepay\PaymentMethod\InstallmentPaymentMethod::class,
    Provider\Ratepay\PaymentMethod\InvoicePaymentMethod::class,
    Provider\SofortBanking\PaymentMethod\StandardPaymentMethod::class,
    Provider\Trustly\PaymentMethod\StandardPaymentMethod::class,
    Provider\WeChatPay\PaymentMethod\StandardPaymentMethod::class,
];
