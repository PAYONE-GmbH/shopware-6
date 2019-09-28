<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCardCheck;

use Shopware\Core\Framework\Struct\Struct;

class CreditCardCheckRequest
{
    public function getRequestParameters(): Struct
    {
        
        
        return Struct::createFrom([
            'request'       => 'creditcardcheck',
            'storecarddata' => 'yes',
        ]);
    }
}
