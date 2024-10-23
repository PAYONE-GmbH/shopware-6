<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;

class PayonePaypalV2Express extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_paypal_v2_express';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Paypal Express v2';

    protected string $description = 'Pay easily and secure with PayPal Express v2.';

    protected string $paymentHandler = PayonePaypalV2ExpressPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE PayPal Express v2',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal Express v2.',
        ],
        'en-GB' => [
            'name' => 'PAYONE PayPal Express v2',
            'description' => 'Pay easily and secure with PayPal Express v2.',
        ],
    ];

    protected int $position = 103;
}
