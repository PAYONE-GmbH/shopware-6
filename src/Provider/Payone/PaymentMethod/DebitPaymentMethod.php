<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\DebitPaymentHandler;

class DebitPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '1b017bef157b4222b734659361d996fd';

    final public const TECHNICAL_NAME = 'payone_debit';

    final public const CONFIGURATION_PREFIX = 'debit';

    public function __construct()
    {
        parent::__construct(
            DebitPaymentHandler::class,
            true,
            'PAYONE Lastschrift',
            null,
            'We\'ll automatically debit the amount from your bank account.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Lastschrift',
                    'description' => 'Wir ziehen den Betrag bequem und automatisch von Ihrem Bankkonto ein.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Direct Debit',
                    'description' => 'We\'ll automatically debit the amount from your bank account.',
                ],
            ],
            101,
            '@Storefront/storefront/payone/debit/debit-form.html.twig',
        );
    }

    #[\Override]
    public static function getId(): string
    {
        return self::UUID;
    }

    #[\Override]
    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    #[\Override]
    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
