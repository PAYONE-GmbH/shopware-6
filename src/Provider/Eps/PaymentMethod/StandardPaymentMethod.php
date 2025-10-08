<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Eps\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Eps\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '6004c8b082234ba5b2834da9874c5ec7';

    final public const TECHNICAL_NAME = 'payone_eps';

    final public const CONFIGURATION_PREFIX = 'eps';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE eps',
            null,
            'Wire the amount instantly with your online banking credentials.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE eps Überweisung',
                    'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE eps',
                    'description' => 'Wire the amount instantly with your online banking credentials.',
                ],
            ],
            113,
            '@Storefront/storefront/payone/eps/eps-form.html.twig',
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
