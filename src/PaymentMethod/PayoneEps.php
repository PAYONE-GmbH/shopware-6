<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;

class PayoneEps extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE eps';

    protected string $description = 'Wire the amount instantly with your online banking credentials.';

    protected string $paymentHandler = PayoneEpsPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/eps/eps-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE eps Überweisung',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name' => 'PAYONE eps',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    protected int $position = 113;
}
