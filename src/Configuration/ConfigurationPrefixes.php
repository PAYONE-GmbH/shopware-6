<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PayonePayment\PaymentHandler as Handler;

interface ConfigurationPrefixes
{
    public const CONFIGURATION_PREFIX_CREDITCARD = 'creditCard';
    public const CONFIGURATION_PREFIX_DEBIT = 'debit';
    public const CONFIGURATION_PREFIX_PAYPAL = 'paypal';
    public const CONFIGURATION_PREFIX_PAYPAL_EXPRESS = 'paypalExpress';
    public const CONFIGURATION_PREFIX_PAYOLUTION_INVOICING = 'payolutionInvoicing';
    public const CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT = 'payolutionInstallment';
    public const CONFIGURATION_PREFIX_PAYOLUTION_DEBIT = 'payolutionDebit';
    public const CONFIGURATION_PREFIX_SOFORT = 'sofort';
    public const CONFIGURATION_PREFIX_EPS = 'eps';
    public const CONFIGURATION_PREFIX_IDEAL = 'iDeal';
    public const CONFIGURATION_PREFIX_PAYDIREKT = 'paydirekt';
    public const CONFIGURATION_PREFIX_PREPAYMENT = 'prepayment';
    public const CONFIGURATION_PREFIX_TRUSTLY = 'trustly';
    public const CONFIGURATION_PREFIX_SECURE_INVOICE = 'secureInvoice';
    public const CONFIGURATION_PREFIX_OPEN_INVOICE = 'openInvoice';
    public const CONFIGURATION_PREFIX_APPLE_PAY = 'applePay';
    public const CONFIGURATION_PREFIX_BANCONTACT = 'bancontact';
    public const CONFIGURATION_PREFIX_RATEPAY_DEBIT = 'ratepayDebit';
    public const CONFIGURATION_PREFIX_RATEPAY_INSTALLMENT = 'ratepayInstallment';
    public const CONFIGURATION_PREFIX_RATEPAY_INVOICING = 'ratepayInvoicing';
    public const CONFIGURATION_PREFIX_KLARNA_INVOICE = 'klarnaInvoice';
    public const CONFIGURATION_PREFIX_KLARNA_DIRECT_DEBIT = 'klarnaDirectDebit';
    public const CONFIGURATION_PREFIX_KLARNA_INSTALLMENT = 'klarnaInstallment';
    public const CONFIGURATION_PREFIX_PRZELEWY24 = 'przelewy24';
    public const CONFIGURATION_PREFIX_WE_CHAT_PAY = 'weChatPay';
    public const CONFIGURATION_PREFIX_POSTFINANCE_CARD = 'postfinanceCard';
    public const CONFIGURATION_PREFIX_POSTFINANCE_WALLET = 'postfinanceWallet';
    public const CONFIGURATION_PREFIX_ALIPAY = 'alipay';
    public const CONFIGURATION_PREFIX_SECURED_INVOICE = 'securedInvoice';
    public const CONFIGURATION_PREFIX_SECURED_INSTALLMENT = 'securedInstallment';
    public const CONFIGURATION_PREFIX_SECURED_DIRECT_DEBIT = 'securedDirectDebit';

    public const CONFIGURATION_PREFIXES = [
        Handler\PayoneApplePayPaymentHandler::class => self::CONFIGURATION_PREFIX_APPLE_PAY,
        Handler\PayoneCreditCardPaymentHandler::class => self::CONFIGURATION_PREFIX_CREDITCARD,
        Handler\PayoneDebitPaymentHandler::class => self::CONFIGURATION_PREFIX_DEBIT,
        Handler\PayonePaypalPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYPAL,
        Handler\PayonePaypalExpressPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYPAL_EXPRESS,
        Handler\PayonePayolutionInvoicingPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYOLUTION_INVOICING,
        Handler\PayonePayolutionInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT,
        Handler\PayonePayolutionDebitPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYOLUTION_DEBIT,
        Handler\PayoneSofortBankingPaymentHandler::class => self::CONFIGURATION_PREFIX_SOFORT,
        Handler\PayoneEpsPaymentHandler::class => self::CONFIGURATION_PREFIX_EPS,
        Handler\PayoneIDealPaymentHandler::class => self::CONFIGURATION_PREFIX_IDEAL,
        Handler\PayonePaydirektPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYDIREKT,
        Handler\PayonePrepaymentPaymentHandler::class => self::CONFIGURATION_PREFIX_PREPAYMENT,
        Handler\PayoneTrustlyPaymentHandler::class => self::CONFIGURATION_PREFIX_TRUSTLY,
        Handler\PayoneSecureInvoicePaymentHandler::class => self::CONFIGURATION_PREFIX_SECURE_INVOICE,
        Handler\PayoneOpenInvoicePaymentHandler::class => self::CONFIGURATION_PREFIX_OPEN_INVOICE,
        Handler\PayoneBancontactPaymentHandler::class => self::CONFIGURATION_PREFIX_BANCONTACT,
        Handler\PayoneRatepayDebitPaymentHandler::class => self::CONFIGURATION_PREFIX_RATEPAY_DEBIT,
        Handler\PayoneRatepayInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_RATEPAY_INSTALLMENT,
        Handler\PayoneRatepayInvoicingPaymentHandler::class => self::CONFIGURATION_PREFIX_RATEPAY_INVOICING,
        Handler\PayoneKlarnaInvoicePaymentHandler::class => self::CONFIGURATION_PREFIX_KLARNA_INVOICE,
        Handler\PayoneKlarnaDirectDebitPaymentHandler::class => self::CONFIGURATION_PREFIX_KLARNA_DIRECT_DEBIT,
        Handler\PayoneKlarnaInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_KLARNA_INSTALLMENT,
        Handler\PayonePrzelewy24PaymentHandler::class => self::CONFIGURATION_PREFIX_PRZELEWY24,
        Handler\PayoneWeChatPayPaymentHandler::class => self::CONFIGURATION_PREFIX_WE_CHAT_PAY,
        Handler\PayonePostfinanceCardPaymentHandler::class => self::CONFIGURATION_PREFIX_POSTFINANCE_CARD,
        Handler\PayonePostfinanceWalletPaymentHandler::class => self::CONFIGURATION_PREFIX_POSTFINANCE_WALLET,
        Handler\PayoneAlipayPaymentHandler::class => self::CONFIGURATION_PREFIX_ALIPAY,
        Handler\PayoneSecuredInvoicePaymentHandler::class => self::CONFIGURATION_PREFIX_SECURED_INVOICE,
        Handler\PayoneSecuredInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_SECURED_INSTALLMENT,
        Handler\PayoneSecuredDirectDebitPaymentHandler::class => self::CONFIGURATION_PREFIX_SECURED_DIRECT_DEBIT,
    ];
}
