<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;

class PayoneKlarnaInvoice extends AbstractPayoneKlarna
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'PAYONE Klarna Rechnung';

    /** @var string */
    protected $description = 'Pay with Klarna open invoice.';

    /** @var string */
    protected $paymentHandler = PayoneKlarnaInvoicePaymentHandler::class;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name.
            'description' => 'Zahle mit dem Klarna Rechnungskauf.',
        ],
        'en-GB' => [
            // do not add en_GB translation for the name.
            'description' => 'Pay with Klarna open invoice.',
        ],
    ];

    /** @var int */
    protected $position = 130;

}
