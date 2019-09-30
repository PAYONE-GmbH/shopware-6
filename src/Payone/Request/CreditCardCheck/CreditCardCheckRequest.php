<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCardCheck;

class CreditCardCheckRequest
{
    public function getRequestParameters(): array
    {
        return [
            'request'       => 'creditcardcheck',
            'storecarddata' => 'yes',
        ];
    }
}
