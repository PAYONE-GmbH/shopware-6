<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler;

class PayoneKlarnaInstallment extends AbstractPayoneKlarna
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'PAYONE Klarna Ratenkauf'; // do not replace this by an english wording. (this is the product name)

    /** @var string */
    protected $description = 'Pay with Klarna installments.';

    /** @var string */
    protected $paymentHandler = PayoneKlarnaInstallmentPaymentHandler::class;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Zahle mit dem Klarna Ratenkauf.',
        ],
        'en-GB' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Pay with Klarna installments.',
        ],
    ];

    /** @var int */
    protected $position = 150;
}
