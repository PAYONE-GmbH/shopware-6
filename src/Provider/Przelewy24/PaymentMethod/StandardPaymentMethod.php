<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Przelewy24\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Przelewy24\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '6068e01cef8b4c9698956c6cca648d50';

    final public const TECHNICAL_NAME = 'payone_przelewy24';

    final public const CONFIGURATION_PREFIX = 'przelewy24';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE Przelewy24',
            null,
            'Pay save and secured with P24',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Przelewy24',
                    'description' => 'Zahle sicher und geschützt mit P24',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Przelewy24',
                    'description' => 'Pay save and secured with P24',
                ],
            ],
            160,
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
