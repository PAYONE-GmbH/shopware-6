<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Paydirekt\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\Provider\Paydirekt\PaymentHandler\StandardPaymentHandler;

/**
 * @deprecated No longer supported
 */
class StandardPaymentMethod extends AbstractPaymentMethod implements NoLongerSupportedPaymentMethodInterface
{
    final public const UUID = 'b5b52a27e6b14a37bbb4087ec821b0f4';

    final public const TECHNICAL_NAME = 'payone_paydirekt';

    final public const CONFIGURATION_PREFIX = 'paydirekt';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE paydirekt',
            null,
            'Pay safe and easy with Paydirekt.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE paydirekt',
                    'description' => 'Zahlen Sie sicher und bequem mit paydirekt.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE paydirekt',
                    'description' => 'Pay safe and easy with paydirekt.',
                ],
            ],
            116,
        );
    }

    public static function getId(): string
    {
        return self::UUID;
    }

    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
