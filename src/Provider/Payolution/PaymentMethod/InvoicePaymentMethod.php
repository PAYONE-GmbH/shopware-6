<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payolution\PaymentHandler\InvoicePaymentHandler;

class InvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '0407fd0a5c4b4d2bafc88379efe8cf8d';

    final public const TECHNICAL_NAME = 'payone_unzer_invoice';

    final public const CONFIGURATION_PREFIX = 'unzerInvoice';

    public function __construct()
    {
        parent::__construct(
            InvoicePaymentHandler::class,
            true,
            'PAYONE Unzer Rechnungskauf',
            null,
            'Invoice payment by Paysafe Pay Later.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Unzer Rechnungskauf',
                    'description' => 'Sie zahlen entspannt nach Erhalt der Ware auf Rechnung.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Unzer Rechnungskauf',
                    'description' => 'Pay the invoice after receiving the goods.',
                ],
            ],
            105,
            '@Storefront/storefront/payone/payolution/payolution-invoicing-form.html.twig',
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
