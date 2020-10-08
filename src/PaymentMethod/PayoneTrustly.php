<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;

class PayoneTrustly extends AbstractPaymentMethod
{
    public const UUID = '741f1deec67d4012bd3ccce265b2e15e';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Trustly';

    /** @var string */
    protected $description = 'Wire the amount instantly with your online banking credentials.';

    /** @var string */
    protected $paymentHandler = PayoneTrustlyPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/trustly/trustly-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Trustly',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name'        => 'Payone Trustly',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    /** @var int */
    protected $position = 125;
}
