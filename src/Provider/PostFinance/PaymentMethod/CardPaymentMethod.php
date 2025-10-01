<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\PostFinance\PaymentHandler\CardPaymentHandler;

class CardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '8b4503f88a7746069a670e1689908832';

    final public const TECHNICAL_NAME = 'payone_postfinance_card';

    final public const CONFIGURATION_PREFIX = 'postfinanceCard';

    public function __construct()
    {
        parent::__construct(
            CardPaymentHandler::class,
            true,
            'PAYONE PostFinance (Card)',
            null,
            'Pay easily and secure with PostFinance (Card).',
            [
                'de-DE' => [
                    'name'        => 'PAYONE PostFinance (Card)',
                    'description' => 'Zahlen Sie sicher und bequem mit PostFinance (Card).',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE PostFinance (card)',
                    'description' => 'Pay easily and secure with PostFinance (Card).',
                ],
            ],
            170,
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
