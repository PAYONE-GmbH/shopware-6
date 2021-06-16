<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardCheckStruct extends Struct
{
    use DeterminationTrait;

    /** @var SalesChannelContext */
    protected $salesChannelContext;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethod
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }
}
