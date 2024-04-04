<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;

class PayoneKlarnaInvoice extends AbstractPayoneKlarna
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_klarna_invoice';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Klarna Rechnung'; // do not replace this by an english wording. (this is the product name)

    protected string $description = 'Pay with Klarna open invoice.';

    protected string $paymentHandler = PayoneKlarnaInvoicePaymentHandler::class;

    protected array $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Zahle mit dem Klarna Rechnungskauf.',
        ],
        'en-GB' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Pay with Klarna open invoice.',
        ],
    ];

    protected int $position = 130;
}
