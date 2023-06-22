<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ApplePayTransactionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use RequestDataTrait;

    public function __construct(
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = '',
        protected ?string $orderId = null
    ) {
        $this->requestData = $requestData;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod = $paymentMethod;
        $this->action = $action;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }
}
