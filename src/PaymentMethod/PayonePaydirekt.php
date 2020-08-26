<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;

class PayonePaydirekt extends AbstractPaymentMethod
{
    public const UUID = 'b5b52a27e6b14a37bbb4087ec821b0f4';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Paydirekt';

    /** @var string */
    protected $description = 'Pay safe and easy with Paydirekt.';

    /** @var string */
    protected $paymentHandler = PayonePaydirektPaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE Paydirekt',
            'description' => 'Zahlen Sie sicher und bequem mit Paydirekt.',
        ],
        'en-GB' => [
            'name'        => 'PAYONE Paydirekt',
            'description' => 'Pay safe and easy with Paydirekt.',
        ],
    ];

    /** @var int */
    protected $position = 116;
}
