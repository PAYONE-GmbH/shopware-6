<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var string */
    protected $paymentHandler;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations;

    /** @var int */
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
