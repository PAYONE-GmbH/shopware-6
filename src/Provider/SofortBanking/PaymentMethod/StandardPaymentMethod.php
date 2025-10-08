<?php

declare(strict_types=1);

namespace PayonePayment\Provider\SofortBanking\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\Provider\SofortBanking\PaymentHandler\StandardPaymentHandler;

/**
 * TODO: only valid in DE, AT, CH, NL. Use ruleEngine to enforce this during the checkout
 */
class StandardPaymentMethod extends AbstractPaymentMethod implements NoLongerSupportedPaymentMethodInterface
{
    final public const UUID = '9022c4733d14411e84a78707088487aa';

    final public const TECHNICAL_NAME = 'payone_sofort';

    final public const CONFIGURATION_PREFIX = 'sofort';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE Sofort',
            null,
            'Wire the amount instantly with your online banking credentials.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Sofort Überweisung',
                    'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Sofort',
                    'description' => 'Wire the amount instantly with your online banking credentials.',
                ],
            ],
            106,
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
