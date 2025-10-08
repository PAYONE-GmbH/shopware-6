<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;

class InvoicePaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '240dcc8bf5fc409c9dcf840698c082aa';

    final public const TECHNICAL_NAME = 'payone_ratepay_invoice';

    final public const CONFIGURATION_PREFIX = 'ratepayInvoicing';

    public function __construct()
    {
        parent::__construct(
            InvoicePaymentHandler::class,
            true,
            'PAYONE Ratepay Open Invoice',
            null,
            'Pay with Ratepay Open Invoice',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Ratepay Rechnungskauf',
                    'description' => 'Zahle mit dem Ratepay Rechnungskauf',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Ratepay Open Invoice',
                    'description' => 'Pay with Ratepay Open Invoice',
                ],
            ],
            130,
            '@Storefront/storefront/payone/ratepay/ratepay-invoicing-form.html.twig',
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
