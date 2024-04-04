<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    protected string $id;

    protected string $name;

    protected string $description;

    protected string $paymentHandler;

    protected ?string $template = null;

    protected array $translations;

    protected int $position;

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

    final public function getTechnicalName(): string
    {
        return \constant(static::class . '::TECHNICAL_NAME');
    }
}
