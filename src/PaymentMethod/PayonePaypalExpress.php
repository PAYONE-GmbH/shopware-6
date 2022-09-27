<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;

class PayonePaypalExpress extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Paypal Express';

    protected string $description = 'Pay easily and secure with PayPal Express.';

    protected string $paymentHandler = PayonePaypalExpressPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE PayPal Express',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal Express.',
        ],
        'en-GB' => [
            'name' => 'PAYONE PayPal Express',
            'description' => 'Pay easily and secure with PayPal Express.',
        ],
    ];

    protected int $position = 103;
}
