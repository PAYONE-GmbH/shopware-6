<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;

class PayoneTrustly extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Trustly';

    protected string $description = 'Wire the amount instantly with your online banking credentials.';

    protected string $paymentHandler = PayoneTrustlyPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/trustly/trustly-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Trustly',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Trustly',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    protected int $position = 125;
}
