<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneCreditCard implements PaymentMethodInterface
{
    /** @var string */
    private $name = 'Payone Credit Card';

    /** @var string */
    private $technicalName = 'payone_credit_card';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneCreditCardPaymentHandler::class;

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
