<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;

class PayoneBancontact extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_bancontact';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Bancontact';

    protected string $description = 'Pay fast and secure with your Bancontact card';

    protected string $paymentHandler = PayoneBancontactPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Bancontact',
            'description' => 'Schnell und einfach bezahlen mit der Bancontact-Karte',
        ],
        'en-GB' => [
            'name' => 'PAYONE Bancontact',
            'description' => 'Pay fast and secure with your Bancontact card',
        ],
    ];

    protected int $position = 120;
}
