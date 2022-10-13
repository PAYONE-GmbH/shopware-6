<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

trait WorkOrderIdTrait
{
    protected string $workorderId;

    public function getWorkorderId(): string
    {
        return $this->workorderId;
    }
}
