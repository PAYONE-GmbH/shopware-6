<?php

declare(strict_types=1);

namespace PayonePayment\Provider\IDeal\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\IDeal\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '3f567ad46f1947e3960b66ed3af537aa';

    final public const TECHNICAL_NAME = 'payone_ideal';

    final public const CONFIGURATION_PREFIX = 'iDeal';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE iDEAL',
            null,
            'Wire the amount instantly with your online banking credentials.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE iDEAL',
                    'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE iDEAL',
                    'description' => 'Wire the amount instantly with your online banking credentials.',
                ],
            ],
            110,
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
