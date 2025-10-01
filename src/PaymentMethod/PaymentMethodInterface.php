<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;

interface PaymentMethodInterface
{
    public static function getId(): string;

    public static function getTechnicalName(): string;

    public static function getConfigurationPrefix(): string;

    /**
     * @return class-string<PaymentHandlerInterface>
     */
    public function getPaymentHandlerClassName(): string;

    public function getName(): string;

    public function getAdministrationLabel(): array|string|null;

    public function getDescription(): string;

    public function getTranslations(): array;

    public function getPosition(): int;

    public function getTemplate(): string|null;

    public function isAfterOrderPayment(): bool;
}
