<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Trustly\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\Provider\Trustly\PaymentHandler\StandardPaymentHandler;

/**
 * @deprecated No longer supported
 */
class StandardPaymentMethod extends AbstractPaymentMethod implements NoLongerSupportedPaymentMethodInterface
{
    final public const UUID = '741f1deec67d4012bd3ccce265b2e15e';

    final public const TECHNICAL_NAME = 'payone_trustly';

    final public const CONFIGURATION_PREFIX = 'trustly';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE Trustly',
            null,
            'Wire the amount instantly with your online banking credentials.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Trustly',
                    'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Trustly',
                    'description' => 'Wire the amount instantly with your online banking credentials.',
                ],
            ],
            125,
            '@Storefront/storefront/payone/trustly/trustly-form.html.twig',
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
