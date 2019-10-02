<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaysafeInstallmentPaymentHandler;

class PayonePaysafeInstallment implements PaymentMethodInterface
{
    public const UUID = '569b46970ad2458ca8f17f1ebb754137';

    /** @var string */
    private $name = 'Payone Paysafe Installment';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayonePaysafeInstallmentPaymentHandler::class;

    /** @var null|string */
    private $template = 'paysafe-installment-form.html.twig';

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone Paysafe Installment',
            'description' => '',
        ],
        'en-GB' => [
            'name'        => 'Payone Paysafe Installment',
            'description' => '',
        ],
    ];

    /** @var int */
    private $position = 104;

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
