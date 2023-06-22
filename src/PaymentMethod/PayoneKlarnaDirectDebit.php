<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;

class PayoneKlarnaDirectDebit extends AbstractPayoneKlarna
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Klarna Sofort bezahlen'; // do not replace this by an english wording. (this is the product name)

    protected string $description = 'Pay with Klarna direct debit.';

    protected string $paymentHandler = PayoneKlarnaDirectDebitPaymentHandler::class;

    protected array $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Zahle mit der Klarna Lastschrift.',
        ],
        'en-GB' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Pay with Klarna direct debit.',
        ],
    ];

    protected int $position = 140;
}
