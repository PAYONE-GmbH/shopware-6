<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;

class PayoneDebit implements PaymentMethodInterface
{
    public const UUID = '1b017bef157b4222b734659361d996fd';

    /** @var string */
    private $name = 'Payone Debit';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneDebitPaymentHandler::class;

    public function getId(): string
    {
        return self::UUID;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }
}
