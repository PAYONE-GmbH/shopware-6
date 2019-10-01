<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaysafeInvoicingPaymentHandler;

class PayonePaysafeInvoicing implements PaymentMethodInterface
{
    public const UUID = '0407fd0a5c4b4d2bafc88379efe8cf8d';

    /** @var string */
    private $name = 'Payone Paysafe Invoicing';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayonePaysafeInvoicingPaymentHandler::class;

    /** @var null|string */
    private $template = 'paysafe-invoicing-form.html.twig';

    /** @var array  */
    private $translations = [];

    /** @var int */
    private $position = 105;

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
