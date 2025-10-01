<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

enum FieldBlacklistEnum: string
{
    case KEY                = 'key';
    case HASH               = 'hash';
    case INTEGRATOR_NAME    = 'integrator_name';
    case INTEGRATOR_VERSION = 'integrator_version';
    case SOLUTION_NAME      = 'solution_name';
    case SOLUTION_VERSION   = 'solution_version';
}
