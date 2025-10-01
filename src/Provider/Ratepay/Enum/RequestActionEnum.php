<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\Enum;

enum RequestActionEnum: string
{
    case RATEPAY_PROFILE     = 'ratepayProfile';
    case RATEPAY_CALCULATION = 'ratepayCalculation';
}
