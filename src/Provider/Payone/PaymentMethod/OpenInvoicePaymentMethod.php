<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\OpenInvoicePaymentHandler;

class OpenInvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '9024aa5a502b4544a745b6b64b486e21';

    final public const TECHNICAL_NAME = 'payone_open_invoice';

    final public const CONFIGURATION_PREFIX = 'openInvoice';

    public function __construct()
    {
        parent::__construct(
            OpenInvoicePaymentHandler::class,
            false,
            'PAYONE Invoice',
            null,
            'Open invoice payment.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Rechnungskauf',
                    'description' => 'Bezahlen per Rechnung.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Invoice',
                    'description' => 'Pay by invoice.',
                ],
            ],
            115,
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
