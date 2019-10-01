<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;

class PayonePaypal implements PaymentMethodInterface
{
    public const UUID = '21e157163fdb4aa4862a2109abcd7522';

    /** @var string */
    private $name = 'Payone Paypal';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayonePaypalPaymentHandler::class;

    /** @var null|string */
    private $template;

    /** @var array  */
    private $translations = [];

    /** @var int */
    private $position = 102;

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
