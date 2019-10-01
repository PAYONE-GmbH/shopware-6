<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

interface PaymentMethodInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getPaymentHandler(): string;

    public function getTemplate(): ?string;

    public function getTranslations(): array;

    public function getPosition(): int;
}
