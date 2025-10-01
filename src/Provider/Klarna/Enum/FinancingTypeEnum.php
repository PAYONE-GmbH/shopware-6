<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\Enum;

enum FinancingTypeEnum: string
{
    case DIRECT_DEBIT = 'KDD';
    case INSTALLMENT  = 'KIS';
    case INVOICE      = 'KIV';
}
