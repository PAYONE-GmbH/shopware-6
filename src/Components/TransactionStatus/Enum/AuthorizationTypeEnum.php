<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus\Enum;

enum AuthorizationTypeEnum: string
{
    case AUTHORIZATION    = 'authorization';
    case PREAUTHORIZATION = 'preauthorization';
}
