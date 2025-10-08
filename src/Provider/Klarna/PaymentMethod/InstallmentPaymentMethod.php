<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Klarna\PaymentHandler\InstallmentPaymentHandler;

class InstallmentPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'a18ffddd4baf4948b8c9f9d3d8abd2d4';

    final public const TECHNICAL_NAME = 'payone_klarna_installment';

    final public const CONFIGURATION_PREFIX = 'klarnaInstallment';

    public function __construct()
    {
        parent::__construct(
            InstallmentPaymentHandler::class,
            true,
            'PAYONE Klarna Ratenkauf',
            null,
            'Pay with Klarna installments.',
            [
                'de-DE' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Zahle mit dem Klarna Ratenkauf.',
                ],

                'en-GB' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Pay with Klarna installments.',
                ],
            ],
            150,
            '@Storefront/storefront/payone/klarna/klarna.html.twig',
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
