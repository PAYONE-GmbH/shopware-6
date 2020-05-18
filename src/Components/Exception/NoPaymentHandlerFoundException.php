<?php

declare(strict_types=1);

namespace PayonePayment\Components\Exception;

use Exception;

class NoPaymentHandlerFoundException extends Exception
{
    public function __construct(string $orderNumber)
    {
        parent::__construct(sprintf('No payment handler was found for order: %s', $orderNumber));
    }
}
