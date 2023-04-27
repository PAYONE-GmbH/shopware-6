<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\CartTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\WorkOrderIdTrait;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CheckoutDetailsStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use CartTrait;
    use WorkOrderIdTrait;

    public function __construct(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action,
        protected string $returnUrl = '',
        string $workorderId = ''
    ) {
        $this->cart = $cart;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod = $paymentMethod;
        $this->action = $action;
        $this->workorderId = $workorderId;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }
}
