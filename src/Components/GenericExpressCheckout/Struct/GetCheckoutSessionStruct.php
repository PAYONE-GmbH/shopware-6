<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout\Struct;

use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetCheckoutSessionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    public function __construct(
        public readonly SalesChannelContext $context,
        public readonly string $workOrderId,
        string $paymentMethod
    ) {
        $this->salesChannelContext = $this->context;
        $this->paymentMethod = $paymentMethod;
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    /**
     * @internal do not use this
     * @deprecated may be removed in the future
     */
    public function getAction(): string
    {
        return '';
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
