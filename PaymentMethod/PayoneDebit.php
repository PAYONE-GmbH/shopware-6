<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;

class PayoneDebit implements PaymentMethodInterface
{
    /** @var string */
    private $name = 'Payone Debit';

    /** @var string */
    private $technicalName = 'payone_debit';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneDebitPaymentHandler::class;

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
