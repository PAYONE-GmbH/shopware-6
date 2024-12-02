<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalV2PaymentHandler;

class PayonePaypalV2 extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_paypal_v2';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE PayPal v2';

    protected string $description = 'Pay easily and secure with PayPal v2.';

    protected string $paymentHandler = PayonePaypalV2PaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE PayPal v2',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal v2.',
        ],
        'en-GB' => [
            'name' => 'PAYONE PayPal v2',
            'description' => 'Pay easily and secure with PayPal v2.',
        ],
    ];

    protected int $position = 102;
}
