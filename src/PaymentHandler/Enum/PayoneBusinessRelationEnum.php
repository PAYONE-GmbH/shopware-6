<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler\Enum;

enum PayoneBusinessRelationEnum: string
{
    case BUSINESSRELATION_B2B = 'b2b';
    case BUSINESSRELATION_B2C = 'b2c';
}
