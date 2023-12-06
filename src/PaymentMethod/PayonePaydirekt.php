<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;

class PayonePaydirekt extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE paydirekt';

    protected string $description = 'Pay safe and easy with Paydirekt.';

    protected string $paymentHandler = PayonePaydirektPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE paydirekt',
            'description' => 'Zahlen Sie sicher und bequem mit paydirekt.',
        ],
        'en-GB' => [
            'name' => 'PAYONE paydirekt',
            'description' => 'Pay safe and easy with paydirekt.',
        ],
    ];

    protected int $position = 116;
}
