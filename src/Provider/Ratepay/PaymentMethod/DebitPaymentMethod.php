<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;

class DebitPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '48f2034b3c62480a8554781cf9cac574';

    final public const TECHNICAL_NAME = 'payone_ratepay_debit';

    final public const CONFIGURATION_PREFIX = 'ratepayDebit';

    public function __construct()
    {
        parent::__construct(
            DebitPaymentHandler::class,
            true,
            'PAYONE Ratepay Direct Debit',
            null,
            'Pay with Ratepay Direct Debit',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Ratepay Lastschrift',
                    'description' => 'Zahle mit Ratepay Lastschrift',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Ratepay Direct Debit',
                    'description' => 'Pay with Ratepay Direct Debit',
                ],
            ],
            131,
            '@Storefront/storefront/payone/ratepay/ratepay-debit-form.html.twig',
        );
    }

    public static function getId(): string
    {
        return self::UUID;
    }

    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
