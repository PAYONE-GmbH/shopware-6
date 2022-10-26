<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutCartPaymentData extends Struct
{
    public const EXTENSION_NAME = 'payone';

    public const DATA_WORK_ORDER_ID = 'workOrderId';
    public const DATA_CART_HASH = 'cartHash';
    public const DATA_CALCULATION_RESPONSE = 'calculationResponse';

    protected string $workOrderId = '';

    protected string $cartHash = '';

    protected array $calculationResponse = [];

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
