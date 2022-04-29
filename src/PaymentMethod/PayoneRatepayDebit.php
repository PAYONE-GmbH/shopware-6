<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;

class PayoneRatepayDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Ratepay Direct Debit';

    /** @var string */
    protected $description = 'ToDo';

    /** @var string */
    protected $paymentHandler = PayoneRatepayDebitPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Ratepay Lastschrift',
            'description' => 'ToDo',
        ],
        'en-GB' => [
            'name'        => 'Payone Ratepay Direct Debit',
            'description' => 'ToDo',
        ],
    ];

    /** @var int */
    protected $position = 102; // ToDo
}
