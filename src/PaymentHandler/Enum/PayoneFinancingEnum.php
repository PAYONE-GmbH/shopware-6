<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\Enum;

enum PayoneFinancingEnum: string
{
    case PIV = 'PIV';
    case PIN = 'PIN';
    case PDD = 'PDD';
    case PYV = 'PYV';
    case PYS = 'PYS';
    case PYD = 'PYD';
    case RPV = 'RPV';
    case RPS = 'RPS';
    case RPD = 'RPD';
}
