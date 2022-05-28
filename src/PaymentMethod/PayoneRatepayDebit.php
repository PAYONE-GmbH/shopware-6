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
    protected $name = 'PAYONE Ratepay Direct Debit';

    /** @var string */
    protected $description = 'Pay with Ratepay Direct Debit';

    /** @var string */
    protected $paymentHandler = PayoneRatepayDebitPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/ratepay/ratepay-debit-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE Ratepay Lastschrift',
            'description' => 'Zahle mit Ratepay Lastschrift',
        ],
        'en-GB' => [
            'name'        => 'PAYONE Ratepay Direct Debit',
            'description' => 'Pay with Ratepay Direct Debit',
        ],
    ];

    /** @var int */
    protected $position = 130;
}
