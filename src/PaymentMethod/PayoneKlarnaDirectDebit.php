<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;

class PayoneKlarnaDirectDebit extends AbstractPayoneKlarna
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'PAYONE Klarna Sofort bezahlen';

    /** @var string */
    protected $description = 'Pay with Klarna direct debit.';

    /** @var string */
    protected $paymentHandler = PayoneKlarnaDirectDebitPaymentHandler::class;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name.
            'description' => 'Zahle mit der Klarna Lastschrift.',
        ],
        'en-GB' => [
            // do not add en_GB translation for the name.
            'description' => 'Pay with Klarna direct debit.',
        ],
    ];

    /** @var int */
    protected $position = 140;

}
