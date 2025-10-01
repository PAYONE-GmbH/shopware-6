<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\Enum;

enum PayoneStateEnum: string
{
    case COMPLETED = 'completed';
    case PENDING   = 'pending';
}
