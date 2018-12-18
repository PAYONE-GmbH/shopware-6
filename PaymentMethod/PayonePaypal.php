<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;

class PayonePaypal implements PaymentMethodInterface
{
    /** @var string */
    private $name = 'Payone Paypal';

    /** @var string */
    private $technicalName = 'payone_paypal';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayonePaypalPaymentHandler::class;

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
