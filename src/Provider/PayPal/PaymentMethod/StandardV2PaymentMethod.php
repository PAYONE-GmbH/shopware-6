<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\PayPal\PaymentHandler\StandardV2PaymentHandler;

class StandardV2PaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'ee2195f621eb466d809cace908163017';

    final public const TECHNICAL_NAME = 'payone_paypal_v2';

    final public const CONFIGURATION_PREFIX = 'paypalV2';

    public function __construct()
    {
        parent::__construct(
            StandardV2PaymentHandler::class,
            true,
            'PAYONE PayPal',
            'PAYONE PayPal v2',
            'Pay easily and secure with PayPal.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE PayPal',
                    'description' => 'Zahlen Sie sicher und bequem mit PayPal.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE PayPal',
                    'description' => 'Pay easily and secure with PayPal.',
                ],
            ],
            102,
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
