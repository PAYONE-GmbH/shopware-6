<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\Enum;

enum PayoneClearingEnum: string
{
    case DEBIT                = 'elv';
    case WALLET               = 'wlt';
    case FINANCING            = 'fnc';
    case CREDIT_CARD          = 'cc';
    case PREPAYMENT           = 'vor';
    case ONLINE_BANK_TRANSFER = 'sb';
    case INVOICE              = 'rec';
}
