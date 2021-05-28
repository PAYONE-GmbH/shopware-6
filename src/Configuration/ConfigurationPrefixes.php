<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PayonePayment\PaymentHandler as Handler;

interface ConfigurationPrefixes
{
    public const CONFIGURATION_PREFIX_CREDITCARD             = 'creditCard';
    public const CONFIGURATION_PREFIX_DEBIT                  = 'debit';
    public const CONFIGURATION_PREFIX_PAYPAL                 = 'paypal';
    public const CONFIGURATION_PREFIX_PAYPAL_EXPRESS         = 'paypalExpress';
    public const CONFIGURATION_PREFIX_PAYOLUTION_INVOICING   = 'payolutionInvoicing';
    public const CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT = 'payolutionInstallment';
    public const CONFIGURATION_PREFIX_PAYOLUTION_DEBIT       = 'payolutionDebit';
    public const CONFIGURATION_PREFIX_SOFORT                 = 'sofort';
    public const CONFIGURATION_PREFIX_EPS                    = 'eps';
    public const CONFIGURATION_PREFIX_IDEAL                  = 'iDeal';
    public const CONFIGURATION_PREFIX_PAYDIREKT              = 'paydirekt';
    public const CONFIGURATION_PREFIX_PREPAYMENT             = 'prepayment';
    public const CONFIGURATION_PREFIX_TRUSTLY                = 'trustly';
    public const CONFIGURATION_PREFIX_SECURE_INVOICE         = 'secureInvoice';

    public const CONFIGURATION_PREFIXES = [
        Handler\PayoneCreditCardPaymentHandler::class            => self::CONFIGURATION_PREFIX_CREDITCARD,
        Handler\PayoneDebitPaymentHandler::class                 => self::CONFIGURATION_PREFIX_DEBIT,
        Handler\PayonePaypalPaymentHandler::class                => self::CONFIGURATION_PREFIX_PAYPAL,
        Handler\PayonePaypalExpressPaymentHandler::class         => self::CONFIGURATION_PREFIX_PAYPAL_EXPRESS,
        Handler\PayonePayolutionInvoicingPaymentHandler::class   => self::CONFIGURATION_PREFIX_PAYOLUTION_INVOICING,
        Handler\PayonePayolutionInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT,
        Handler\PayonePayolutionDebitPaymentHandler::class       => self::CONFIGURATION_PREFIX_PAYOLUTION_DEBIT,
        Handler\PayoneSofortBankingPaymentHandler::class         => self::CONFIGURATION_PREFIX_SOFORT,
        Handler\PayoneEpsPaymentHandler::class                   => self::CONFIGURATION_PREFIX_EPS,
        Handler\PayoneIDealPaymentHandler::class                 => self::CONFIGURATION_PREFIX_IDEAL,
        Handler\PayonePaydirektPaymentHandler::class             => self::CONFIGURATION_PREFIX_PAYDIREKT,
        Handler\PayonePrepaymentPaymentHandler::class            => self::CONFIGURATION_PREFIX_PREPAYMENT,
        Handler\PayoneTrustlyPaymentHandler::class               => self::CONFIGURATION_PREFIX_TRUSTLY,
        Handler\PayoneSecureInvoicePaymentHandler::class         => self::CONFIGURATION_PREFIX_SECURE_INVOICE,
    ];
}
