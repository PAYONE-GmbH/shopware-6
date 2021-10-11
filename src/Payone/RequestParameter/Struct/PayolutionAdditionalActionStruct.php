<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\CartTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\WorkOrderIdTrait;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionAdditionalActionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use CartTrait;
    use WorkOrderIdTrait;
    use RequestDataTrait;

    public function __construct(
        Cart $cart,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = '',
        string $workorderId = ''
    ) {
        $this->cart                = $cart;
        $this->requestData         = $requestData;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
        $this->action              = $action;
        $this->workorderId         = $workorderId;
    }
}
