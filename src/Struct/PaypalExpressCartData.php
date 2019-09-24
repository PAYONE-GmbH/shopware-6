<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PaypalExpressCartData extends Struct
{
    public const EXTENSION_NAME = 'payone';

    /** @var string */
    protected $workOrderId;

    /** @var string */
    protected $hash;

    public function getWorkorderId(): string
    {
        return $this->workOrderId;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
