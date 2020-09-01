<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;

class PayonePaypal extends AbstractPaymentMethod
{
    public const UUID = '21e157163fdb4aa4862a2109abcd7522';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone PayPal';

    /** @var string */
    protected $description = 'Pay easily and secure with PayPal.';

    /** @var string */
    protected $paymentHandler = PayonePaypalPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone PayPal',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal.',
        ],
        'en-GB' => [
            'name'        => 'Payone PayPal',
            'description' => 'Pay easily and secure with PayPal.',
        ],
    ];

    /** @var int */
    protected $position = 102;
}
