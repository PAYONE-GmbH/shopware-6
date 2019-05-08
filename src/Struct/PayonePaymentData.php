<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PayonePaymentData extends Struct
{
    protected $cardRequest;

    /**
     * @return mixed
     */
    public function getCardRequest()
    {
        return $this->cardRequest;
    }
}
