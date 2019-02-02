<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneSofortPaymentHandler;

class PayoneSofort implements PaymentMethodInterface
{
    /** @var string */
    private $id = '9022c4733d14411e84a78707088487aa';

    /** @var string */
    private $name = 'Payone Sofort';

    /** @var string */
    private $technicalName = 'payone_sofort';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayoneSofortPaymentHandler::class;

    public function getId(): string
    {
        return $this->id;
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
