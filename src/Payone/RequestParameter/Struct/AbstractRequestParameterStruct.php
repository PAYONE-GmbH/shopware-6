<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AbstractRequestParameterStruct extends Struct
{
    protected string $action = '';

    protected string $paymentMethod = '';

    public function getAction(): string
    {
        return $this->action;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
}
