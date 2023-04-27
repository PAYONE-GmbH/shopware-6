<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;

/**
 * TODO: only valid in DE, AT, CH, NL. Use ruleEngine to enforce this during the checkout
 */
class PayoneSofortBanking extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Sofort';

    protected string $description = 'Wire the amount instantly with your online banking credentials.';

    protected string $paymentHandler = PayoneSofortBankingPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Sofort Überweisung',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Sofort',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    protected int $position = 106;
}
