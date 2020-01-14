<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;

class PayoneDebit implements PaymentMethodInterface
{
    public const UUID = '1b017bef157b4222b734659361d996fd';

    /** @var string */
    private $name = 'Payone SEPA Lastschrift';

    /** @var string */
    private $description = 'We\'ll automatically debit the amount from your bank account.';

    /** @var string */
    private $paymentHandler = PayoneDebitPaymentHandler::class;

    /** @var null|string */
    private $template = '@Storefront/storefront/payone/debit/debit-form.html.twig';

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone SEPA Lastschrift',
            'description' => 'Wir ziehen den Betrag bequem und automatisch von Ihrem Bankkonto ein.',
        ],
        'en-GB' => [
            'name'        => 'Payone SEPA Direct Debit',
            'description' => 'We\'ll automatically debit the amount from your bank account.',
        ],
    ];

    /** @var int */
    private $position = 101;

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
