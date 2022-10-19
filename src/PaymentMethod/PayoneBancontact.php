<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;

class PayoneBancontact extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'Payone Bancontact';

    protected string $description = 'Pay fast and secure with your Bancontact card';

    protected string $paymentHandler = PayoneBancontactPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'Payone Bancontact',
            'description' => 'Schnell und einfach bezahlen mit der Bancontact-Karte',
        ],
        'en-GB' => [
            'name' => 'Payone Bancontact',
            'description' => 'Pay fast and secure with your Bancontact card',
        ],
    ];

    protected int $position = 120;
}
