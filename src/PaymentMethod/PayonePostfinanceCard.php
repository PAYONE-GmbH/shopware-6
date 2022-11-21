<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePostfinanceCardPaymentHandler;

class PayonePostfinanceCard extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Postfinance (Card)';

    protected string $description = 'Pay easily and secure with Postfinance (Card).';

    protected string $paymentHandler = PayonePostfinanceCardPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Postfinance (Card)',
            'description' => 'Zahlen Sie sicher und bequem mit Postfinance (Card).',
        ],
        'en-GB' => [
            'name' => 'PAYONE Postfinance (card)',
            'description' => 'Pay easily and secure with Postfinance (Card).',
        ],
    ];

    protected int $position = 170;
}
