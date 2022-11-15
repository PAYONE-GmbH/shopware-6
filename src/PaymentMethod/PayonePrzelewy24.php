<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;

class PayonePrzelewy24 extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Przelewy24';

    protected string $description = 'Pay save and secured with P24';

    protected string $paymentHandler = PayonePrzelewy24PaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Przelewy24',
            'description' => 'Zahle sicher und geschützt mit P24',
        ],
        'en-GB' => [
            'name' => 'PAYONE Przelewy24',
            'description' => 'Pay save and secured with P24',
        ],
    ];

    protected int $position = 160;
}
