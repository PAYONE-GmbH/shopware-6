<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus\Enum;

enum TransactionActionEnum: string
{
    case APPOINTED       = 'appointed';
    case PAID            = 'paid';
    case CAPTURE         = 'capture';
    case PARTIAL_CAPTURE = 'partialCapture';
    case COMPLETED       = 'completed';
    case DEBIT           = 'debit';
    case PARTIAL_DEBIT   = 'partialDebit';
    case CANCELATION     = 'cancelation';
    case FAILED          = 'failed';
    case REDIRECT        = 'redirect';
    case INVOICE         = 'invoice';
    case UNDERPAID       = 'underpaid';
    case TRANSFER        = 'transfer';
    case REMINDER        = 'reminder';
    case VAUTHORIZATION  = 'vauthorization';
    case VSETTLEMENT     = 'vsettlement';
}
