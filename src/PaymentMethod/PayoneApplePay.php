<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneApplePayPaymentHandler;

class PayoneApplePay extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Apple Pay';

    /** @var string */
    protected $description = 'Apple Pay is a mobile payment system providing straightforward payment on Apple devices';

    /** @var string */
    protected $paymentHandler = PayoneApplePayPaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Apple Pay',
            'description' => 'Apple Pay ist ein mobiles Zahlungssystem, welches die bequeme Zahlung auf Endgeräten von Apple ermöglicht.',
        ],
        'en-GB' => [
            'name'        => 'Payone Apple Pay',
            'description' => 'Apple Pay is a mobile payment system providing straightforward payment on Apple devices',
        ],
    ];

    /** @var int */
    protected $position = 100;
}
