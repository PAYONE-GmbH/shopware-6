<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Klarna\PaymentHandler\DirectDebitPaymentHandler;

class DirectDebitPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '31af2cbeda5242bfbfe4531e203f8a42';

    final public const TECHNICAL_NAME = 'payone_klarna_direct_debit';

    final public const CONFIGURATION_PREFIX = 'klarnaDirectDebit';

    public function __construct()
    {
        parent::__construct(
            DirectDebitPaymentHandler::class,
            true,
            'PAYONE Klarna Sofort bezahlen',
            null,
            'Pay with Klarna direct debit.',
            [
                'de-DE' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Zahle mit der Klarna Lastschrift.',
                ],

                'en-GB' => [
                    // do not add de_DE translation for the name. (this::$name is the product name)
                    'description' => 'Pay with Klarna direct debit.',
                ],
            ],
            140,
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
