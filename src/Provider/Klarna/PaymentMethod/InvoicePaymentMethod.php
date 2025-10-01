<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Klarna\PaymentHandler\InvoicePaymentHandler;

class InvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'c4cd059611cc4d049187d8d955ec1f91';

    final public const TECHNICAL_NAME = 'payone_klarna_invoice';

    final public const CONFIGURATION_PREFIX = 'klarnaInvoice';

    public function __construct()
    {
        parent::__construct(
            InvoicePaymentHandler::class,
            true,
            'PAYONE Klarna Rechnung',
            null,
            'Pay with Klarna open invoice.',
            [
                'de-DE' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Zahle mit dem Klarna Rechnungskauf.',
                ],

                'en-GB' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Pay with Klarna open invoice.',
                ],
            ],
            130,
            '@Storefront/storefront/payone/klarna/klarna.html.twig',
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
