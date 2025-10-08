<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Bancontact\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Bancontact\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '32ecec740c7142c9bf51d00ea894ffad';

    final public const TECHNICAL_NAME = 'payone_bancontact';

    final public const CONFIGURATION_PREFIX = 'bancontact';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE Bancontact',
            null,
            'Pay fast and secure with your Bancontact card',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Bancontact',
                    'description' => 'Schnell und einfach bezahlen mit der Bancontact-Karte',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Bancontact',
                    'description' => 'Pay fast and secure with your Bancontact card',
                ],
            ],
            120,
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
