<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

enum RequestConstantsEnum: string
{
    case WORK_ORDER_ID = 'workorder';
    case CART_HASH     = 'carthash';
    case PHONE         = 'payonePhone';
    case BIRTHDAY      = 'payoneBirthday';
}
