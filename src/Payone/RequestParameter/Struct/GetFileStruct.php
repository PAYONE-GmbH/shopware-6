<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetFileStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    /** @var string */
    protected $identification;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $identification
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
        $this->identification      = $identification;
    }

    public function getIdentification(): string
    {
        return $this->identification;
    }
}
