<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    /**
     * @param class-string<PaymentHandlerInterface> $paymentHandlerClassName
     * @param array<string, string>|string|null     $administrationLabel
     */
    public function __construct(
        private readonly string $paymentHandlerClassName,
        private readonly bool $afterOrderPayment,
        private readonly string $name,
        private readonly array|string|null $administrationLabel,
        private readonly string $description,
        private readonly array $translations,
        private readonly int $position,
        private readonly string|null $template = null,
    ) {
    }

    /**
     * @inheritdoc
     */
    final public function getPaymentHandlerClassName(): string
    {
        return $this->paymentHandlerClassName;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function getAdministrationLabel(): array|string|null
    {
        return $this->administrationLabel;
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    final public function getTranslations(): array
    {
        return $this->translations;
    }

    final public function getPosition(): int
    {
        return $this->position;
    }

    final public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function isAfterOrderPayment(): bool
    {
        return $this->afterOrderPayment;
    }
}
