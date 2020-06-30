<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;

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

    public const CONFIGURATION_PREFIXES = [
        PayoneCreditCardPaymentHandler::class            => self::CONFIGURATION_PREFIX_CREDITCARD,
        PayoneDebitPaymentHandler::class                 => self::CONFIGURATION_PREFIX_DEBIT,
        PayonePaypalPaymentHandler::class                => self::CONFIGURATION_PREFIX_PAYPAL,
        PayonePaypalExpressPaymentHandler::class         => self::CONFIGURATION_PREFIX_PAYPAL_EXPRESS,
        PayonePayolutionInvoicingPaymentHandler::class   => self::CONFIGURATION_PREFIX_PAYOLUTION_INVOICING,
        PayonePayolutionInstallmentPaymentHandler::class => self::CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT,
        PayonePayolutionDebitPaymentHandler::class       => self::CONFIGURATION_PREFIX_PAYOLUTION_DEBIT,
        PayoneSofortBankingPaymentHandler::class         => self::CONFIGURATION_PREFIX_SOFORT,
        PayoneEpsPaymentHandler::class                   => self::CONFIGURATION_PREFIX_EPS,
        PayoneIDealPaymentHandler::class                 => self::CONFIGURATION_PREFIX_IDEAL,
    ];
}
