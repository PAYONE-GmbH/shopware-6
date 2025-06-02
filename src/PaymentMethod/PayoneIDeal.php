<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;

class PayoneIDeal extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_ideal';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE iDEAL';

    protected string $description = 'Wire the amount instantly with your online banking credentials.';

    protected string $paymentHandler = PayoneIDealPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE iDEAL',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name' => 'PAYONE iDEAL',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    protected int $position = 110;
}
