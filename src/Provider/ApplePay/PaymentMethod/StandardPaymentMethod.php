<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\ApplePay\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '4cbc89a06e544c06b413a41d158f5e00';

    final public const TECHNICAL_NAME = 'payone_apple_pay';

    final public const CONFIGURATION_PREFIX = 'applePay';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE Apple Pay',
            null,
            'Apple Pay is a mobile payment system providing straightforward payment on Apple devices',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Apple Pay',
                    'description' => 'Apple Pay ist ein mobiles Zahlungssystem, welches die bequeme Zahlung auf Endgeräten von Apple ermöglicht.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Apple Pay',
                    'description' => 'Apple Pay is a mobile payment system providing straightforward payment on Apple devices',
                ],
            ],
            100,
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
