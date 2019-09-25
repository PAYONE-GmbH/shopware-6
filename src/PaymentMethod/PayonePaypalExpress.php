<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;

class PayonePaypalExpress implements PaymentMethodInterface
{
    public const UUID = '5ddf648859a84396a98c97a1a92c107f';

    /** @var string */
    private $name = 'Payone Paypal Express';

    /** @var string */
    private $description = '';

    /** @var string */
    private $paymentHandler = PayonePaypalExpressPaymentHandler::class;

    /** @var null|string */
    private $template;

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
}
