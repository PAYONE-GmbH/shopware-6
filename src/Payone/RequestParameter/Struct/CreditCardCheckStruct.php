<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardCheckStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethod
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
    }
}
