<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionAdditionalActionStruct extends Struct
{
    use DeterminationTrait;

    /** @var RequestDataBag */
    protected $requestData;

    /** @var Cart */
    protected $cart;

    /** @var SalesChannelContext */
    protected $salesChannelContext;

    /** @var string */
    protected $workorderId;

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

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getRequestData(): RequestDataBag
    {
        return $this->requestData;
    }

    public function setRequestData(RequestDataBag $requestData): void
    {
        $this->requestData = $requestData;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getWorkorderId(): string
    {
        return $this->workorderId;
    }

    public function setWorkorderId(string $workorderId): void
    {
        $this->workorderId = $workorderId;
    }
}
