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
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneDebitPaymentHandler::class;

    /** @var null|string */
    private $template = 'debit-form.html.twig';

    /** @var array */
    private $translations = [];

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
