<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payolution\PaymentHandler\DebitPaymentHandler;

class DebitPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '700954775fad4a8f92463b3d629c8ad5';

    final public const TECHNICAL_NAME = 'payone_unzer_debit';

    final public const CONFIGURATION_PREFIX = 'unzerDebit';

    public function __construct()
    {
        parent::__construct(
            DebitPaymentHandler::class,
            true,
            'PAYONE Unzer Lastschrift',
            null,
            'SEPA Direct Debit by Paysafe Pay Later.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Unzer Lastschrift',
                    'description' => 'Gesicherte Lastschrift von Paysafe Pay Later.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Unzer Lastschrift',
                    'description' => 'SEPA Direct Debit by Paysafe Pay Later.',
                ],
            ],
            107,
            '@Storefront/storefront/payone/payolution/payolution-debit-form.html.twig',
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
