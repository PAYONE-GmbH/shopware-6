<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;

class PayoneEps extends AbstractPaymentMethod
{
    public const UUID = '6004c8b082234ba5b2834da9874c5ec7';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone EPS';

    /** @var string */
    protected $description = 'Wire the amount instantly with your online banking credentials.';

    /** @var string */
    protected $paymentHandler = PayoneEpsPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/eps/eps-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE EPS',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name'        => 'PAYONE EPS',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    /** @var int */
    protected $position = 113;
}
