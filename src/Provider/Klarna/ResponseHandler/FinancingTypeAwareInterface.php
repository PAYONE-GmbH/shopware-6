<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\ResponseHandler;

use PayonePayment\Provider\Klarna\Enum\FinancingTypeEnum;

interface FinancingTypeAwareInterface
{
    public function getFinancingType(): FinancingTypeEnum;
}
