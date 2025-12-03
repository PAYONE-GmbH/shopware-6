<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\Enum;

enum CardNetworkEnum: string
{
    case MASTERCARD = 'MASTERCARD';
    case VISA       = 'VISA';
}
