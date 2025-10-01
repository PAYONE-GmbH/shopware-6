<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\Enum;

enum TransferTypeEnum: string
{
    case CARD   = 'PFC';
    case WALLET = 'PFF';
}
