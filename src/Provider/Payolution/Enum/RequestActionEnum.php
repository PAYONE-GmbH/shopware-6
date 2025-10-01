<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\Enum;

enum RequestActionEnum: string
{
    case PAYOLUTION_PRE_CHECK   = 'pre-check';
    case PAYOLUTION_CALCULATION = 'calculation';
}
