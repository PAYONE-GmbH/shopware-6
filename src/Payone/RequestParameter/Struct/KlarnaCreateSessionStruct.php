<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaCreateSessionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethodHandler,
        private readonly ?OrderEntity $orderEntity = null
    ) {
        $this->action = AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod = $paymentMethodHandler;
    }

    public function getOrderEntity(): ?OrderEntity
    {
        return $this->orderEntity;
    }
}
