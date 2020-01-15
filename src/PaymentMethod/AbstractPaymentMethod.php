<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    protected $id;
    protected $name;
    protected $description;
    protected $paymentHandler;
    protected $template = null;
    protected $translations;
    protected $position;

    public function getId(): string
    {
        return $this->id;
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
