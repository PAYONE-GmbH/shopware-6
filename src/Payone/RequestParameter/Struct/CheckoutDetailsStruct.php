<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CheckoutDetailsStruct extends Struct
{
    use DeterminationTrait;

    /** @var Cart */
    protected $cart;

    /** @var string */
    protected $returnUrl;

    /** @var string */
    protected $workorderId;

    /** @var SalesChannelContext */
    protected $salesChannelContext;

    public function __construct(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action,
        string $returnUrl = '',
        string $workorderId = ''
    ) {
        $this->cart                = $cart;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
        $this->action              = $action;
        $this->returnUrl           = $returnUrl;
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

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
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
