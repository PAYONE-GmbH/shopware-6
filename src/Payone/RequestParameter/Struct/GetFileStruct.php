<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetFileStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        protected string $identification
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod = $paymentMethod;
    }

    public function getIdentification(): string
    {
        return $this->identification;
    }
}
