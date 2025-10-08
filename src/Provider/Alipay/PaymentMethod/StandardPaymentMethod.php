<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Alipay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Alipay\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'fef3c750f8e94a6abb7d0a8061ac9faf';

    final public const TECHNICAL_NAME = 'payone_alipay';

    final public const CONFIGURATION_PREFIX = 'alipay';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE Alipay',
            null,
            'Pay save and secured with Alipay',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Alipay',
                    'description' => 'Zahle sicher und geschützt mit Alipay',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Alipay',
                    'description' => 'Pay save and secured with Alipay',
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
