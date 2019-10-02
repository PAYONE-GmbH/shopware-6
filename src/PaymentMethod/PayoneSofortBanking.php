<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;

/**
 * TODO: only valid in DE, AT, CH, NL. Use ruleEngine to enforce this during the checkout
 */
class PayoneSofortBanking implements PaymentMethodInterface
{
    public const UUID = '9022c4733d14411e84a78707088487aa';

    /** @var string */
    private $name = 'Payone Sofort';

    /** @var string */
    private $description = 'Wire the amount instantly with your online banking credentials.';

    /** @var string */
    private $paymentHandler = PayoneSofortBankingPaymentHandler::class;

    /** @var null|string */
    private $template;

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone Sofort',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name'        => 'Payone Sofort',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    /** @var int */
    private $position = 106;

    public function getId(): string
    {
        return self::UUID;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
