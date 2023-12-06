<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;

class PayonePaypal extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE PayPal';

    protected string $description = 'Pay easily and secure with PayPal.';

    protected string $paymentHandler = PayonePaypalPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE PayPal',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal.',
        ],
        'en-GB' => [
            'name' => 'PAYONE PayPal',
            'description' => 'Pay easily and secure with PayPal.',
        ],
    ];

    protected int $position = 102;
}
