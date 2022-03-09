<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;

class PayoneBancontact extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Bancontact';

    /** @var string */
    protected $description = 'Pay fast and secure with your Bancontact card';

    /** @var string */
    protected $paymentHandler = PayoneBancontactPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Bancontact',
            'description' => 'Schnell und einfach bezahlen mit der Bancontact-Karte',
        ],
        'en-GB' => [
            'name'        => 'Payone Bancontact',
            'description' => 'Pay fast and secure with your Bancontact card',
        ],
    ];

    /** @var int */
    protected $position = 120;
}
