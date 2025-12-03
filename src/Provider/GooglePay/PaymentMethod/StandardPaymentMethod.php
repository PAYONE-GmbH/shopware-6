<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\GooglePay\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '019a590fe14672a3a2bfab75c8a355cb';

    final public const TECHNICAL_NAME = 'payone_google_pay';

    final public const CONFIGURATION_PREFIX = 'googlePay';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE Google Pay',
            null,
            'Pay save and secured with Google Pay',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Google Pay',
                    'description' => 'Zahle sicher und geschützt mit Google Pay',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Google Pay',
                    'description' => 'Pay save and secured with Google Pay',
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
