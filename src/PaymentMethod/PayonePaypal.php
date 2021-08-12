<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;

class PayonePaypal extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

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
