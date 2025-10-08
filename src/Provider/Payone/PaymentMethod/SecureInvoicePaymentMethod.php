<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\SecureInvoicePaymentHandler;

class SecureInvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '4e8a9d3d3c6e428887573856b38c9003';

    final public const TECHNICAL_NAME = 'payone_secure_invoice';

    final public const CONFIGURATION_PREFIX = 'secureInvoice';

    public function __construct()
    {
        parent::__construct(
            SecureInvoicePaymentHandler::class,
            false,
            'PAYONE Secure Invoice',
            null,
            'Secure invoice payment.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Gesicherter Rechnungskauf',
                    'description' => 'Abgesichert bezahlen per Rechnung.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Secure Invoice',
                    'description' => 'Secure pay by invoice. After reception of goods.',
                ],
            ],
            114,
            '@Storefront/storefront/payone/secure-invoice/secure-invoice.html.twig',
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
