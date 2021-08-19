<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneApplePay extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Apple Pay';

    //TODO: add description
    /** @var string */
    protected $description = '';

    //TODO: change
    /** @var string */
    protected $paymentHandler = PayoneCreditCardPaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    //TODO: add description
    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Apple Pay',
            'description' => '',
        ],
        'en-GB' => [
            'name'        => 'Payone Apple Pay',
            'description' => '',
        ],
    ];

    /** @var int */
    protected $position = 100;
}
