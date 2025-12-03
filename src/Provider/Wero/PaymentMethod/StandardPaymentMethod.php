<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Wero\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Wero\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '019a589e75d47285bfe430e01c95647a';

    final public const TECHNICAL_NAME = 'payone_wero';

    final public const CONFIGURATION_PREFIX = 'wero';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE Wero',
            null,
            'Pay save and secured with Wero',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Wero',
                    'description' => 'Zahle sicher und geschützt mit Wero',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Wero',
                    'description' => 'Pay save and secured with Wero',
                ],
            ],
            170,
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
