<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePostfinanceWalletPaymentHandler;

class PayonePostfinanceWallet extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_postfinance_wallet';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Postfinance (Wallet)';

    protected string $description = 'Pay easily and secure with Postfinance (Wallet).';

    protected string $paymentHandler = PayonePostfinanceWalletPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Postfinance (Wallet)',
            'description' => 'Zahlen Sie sicher und bequem mit Postfinance (Wallet).',
        ],
        'en-GB' => [
            'name' => 'PAYONE Postfinance (Wallet)',
            'description' => 'Pay easily and secure with Postfinance (Wallet).',
        ],
    ];

    protected int $position = 180;
}
