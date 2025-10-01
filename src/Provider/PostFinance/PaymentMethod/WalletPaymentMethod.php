<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\PostFinance\PaymentHandler\WalletPaymentHandler;

class WalletPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'cd65c7f9c0cc4e0886799f7cc7407494';

    final public const TECHNICAL_NAME = 'payone_postfinance_wallet';

    final public const CONFIGURATION_PREFIX = 'postfinanceWallet';

    public function __construct()
    {
        parent::__construct(
            WalletPaymentHandler::class,
            true,
            'PAYONE PostFinance (Wallet)',
            null,
            'Pay easily and secure with PostFinance (Wallet).',
            [
                'de-DE' => [
                    'name'        => 'PAYONE PostFinance (Wallet)',
                    'description' => 'Zahlen Sie sicher und bequem mit PostFinance (Wallet).',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE PostFinance (Wallet)',
                    'description' => 'Pay easily and secure with PostFinance (Wallet).',
                ],
            ],
            180,
        );
    }

    public static function getId(): string
    {
        return self::UUID;
    }

    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
