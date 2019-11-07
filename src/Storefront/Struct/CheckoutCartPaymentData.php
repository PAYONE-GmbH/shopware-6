<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutCartPaymentData extends Struct
{
    public const EXTENSION_NAME = 'payone';

    /** @var string */
    protected $workOrderId = '';

    /** @var string */
    protected $cartHash = '';

    /** @var array */
    protected $calculationResponse = [];

    public function getWorkorderId(): string
    {
        return $this->workOrderId;
    }

    public function getCartHash(): string
    {
        return $this->cartHash;
    }

    public function getCalculationResponse(): array
    {
        return $this->calculationResponse;
    }
}
