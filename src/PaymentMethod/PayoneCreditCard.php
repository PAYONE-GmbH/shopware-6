<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneCreditCard implements PaymentMethodInterface
{
    public const UUID = '37f90a48d9194762977c9e6db36334e0';

    /** @var string */
    private $name = 'Payone Credit Card';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneCreditCardPaymentHandler::class;

    /** @var null|string */
    private $template = 'credit-card-form.html.twig';

    /** @var array */
    private $translations = [];

    /** @var int */
    private $position = 100;

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
