<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\PayPal\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '21e157163fdb4aa4862a2109abcd7522';

    final public const TECHNICAL_NAME = 'payone_paypal';

    final public const CONFIGURATION_PREFIX = 'paypal';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE PayPal',
            null,
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
