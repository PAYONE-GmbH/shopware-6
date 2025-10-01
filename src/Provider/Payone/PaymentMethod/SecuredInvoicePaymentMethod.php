<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredInvoicePaymentHandler;

class SecuredInvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '4ca01ac1471c4da5b76faeaa42524cc3';

    final public const TECHNICAL_NAME = 'payone_secured_invoice';

    final public const CONFIGURATION_PREFIX = 'securedInvoice';

    public function __construct()
    {
        parent::__construct(
            SecuredInvoicePaymentHandler::class,
            false,
            'PAYONE Secured Invoice',
            [
                'en-GB' => 'PAYONE Secured Invoice (new)',
                'de-DE' => 'PAYONE Gesicherter Rechnungskauf (neu)',
            ],
            'Pay with secured open invoice',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Gesicherter Rechnungskauf',
                    'description' => 'Zahle mit dem gesicherten Rechnungskauf',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Secured Invoice',
                    'description' => 'Pay with secured open invoice',
                ],
            ],
            190,
            '@Storefront/storefront/payone/secured-invoice/secured-invoice.html.twig',
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
