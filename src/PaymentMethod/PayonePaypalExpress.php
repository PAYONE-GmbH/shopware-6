<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;

class PayonePaypalExpress extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Paypal Express';

    /** @var string */
    protected $description = 'Pay easily and secure with PayPal Express.';

    /** @var string */
    protected $paymentHandler = PayonePaypalExpressPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone PayPal Express',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal Express.',
        ],
        'en-GB' => [
            'name'        => 'Payone PayPal Express',
            'description' => 'Pay easily and secure with PayPal Express.',
        ],
    ];

    /** @var int */
    protected $position = 103;
}
