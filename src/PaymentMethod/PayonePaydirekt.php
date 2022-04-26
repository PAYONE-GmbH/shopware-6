<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;

class PayonePaydirekt extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'paydirekt';

    /** @var string */
    protected $description = 'Pay safe and easy with Paydirekt.';

    /** @var string */
    protected $paymentHandler = PayonePaydirektPaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'paydirekt',
            'description' => 'Zahlen Sie sicher und bequem mit paydirekt.',
        ],
        'en-GB' => [
            'name'        => 'paydirekt',
            'description' => 'Pay safe and easy with paydirekt.',
        ],
    ];

    /** @var int */
    protected $position = 116;
}
