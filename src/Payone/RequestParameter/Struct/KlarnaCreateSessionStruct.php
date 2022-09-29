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

    /** @var null|OrderEntity */
    private $orderEntity;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $paymentMethodHandler,
        OrderEntity $orderEntity = null
    ) {
        $this->action              = AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethodHandler;
        $this->orderEntity         = $orderEntity;
    }

    public function getOrderEntity(): ?OrderEntity
    {
        return $this->orderEntity;
    }
}
