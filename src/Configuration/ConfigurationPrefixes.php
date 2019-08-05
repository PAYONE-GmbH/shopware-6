<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;

interface ConfigurationPrefixes
{
    public const CONFIGURATION_PREFIX_CREDITCARD = 'creditCard';
    public const CONFIGURATION_PREFIX_DEBIT      = 'debit';
    public const CONFIGURATION_PREFIX_PAYPAL     = 'paypal';
    public const CONFIGURATION_PREFIX_SOFORT     = 'sofort';

    public const CONFIGURATION_PREFIXES = [
        PayoneCreditCardPaymentHandler::class    => self::CONFIGURATION_PREFIX_CREDITCARD,
        PayoneDebitPaymentHandler::class         => self::CONFIGURATION_PREFIX_DEBIT,
        PayonePaypalPaymentHandler::class        => self::CONFIGURATION_PREFIX_PAYPAL,
        PayoneSofortBankingPaymentHandler::class => self::CONFIGURATION_PREFIX_SOFORT,
    ];
}
