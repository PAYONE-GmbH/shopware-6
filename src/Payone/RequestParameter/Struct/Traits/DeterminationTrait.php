<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

trait DeterminationTrait
{
    /** @var string */
    protected $action = '';

    /** @var string */
    protected $paymentMethod = '';

    public function getAction(): string
    {
        return $this->action;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
}
