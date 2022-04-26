<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;

class PayoneEps extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'eps';

    /** @var string */
    protected $description = 'Wire the amount instantly with your online banking credentials.';

    /** @var string */
    protected $paymentHandler = PayoneEpsPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/eps/eps-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'eps Überweisung',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name'        => 'eps',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    /** @var int */
    protected $position = 113;
}
