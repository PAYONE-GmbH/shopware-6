<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentFilterContext extends Struct
{
    private SalesChannelContext $salesChannelContext;

    /**
     * @var CustomerAddressEntity|OrderAddressEntity|null
     */
    private $billingAddress = null;

    /**
     * @var CustomerAddressEntity|OrderAddressEntity|null
     */
    private $shippingAddress = null;

    private ?CurrencyEntity $currency = null;

    private ?OrderEntity $order = null;

    private ?Cart $cart = null;

    /**
     * @param CustomerAddressEntity|OrderAddressEntity|null $billingAddress
     * @param CustomerAddressEntity|OrderAddressEntity|null $shippingAddress
     */
    public function __construct(
        SalesChannelContext $salesChannelContext,
        $billingAddress = null,
        $shippingAddress = null,
        ?CurrencyEntity $currency = null,
        ?OrderEntity $order = null,
        ?Cart $cart = null
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->currency = $currency;
        $this->order = $order;
        $this->cart = $cart;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    /**
     * @return CustomerAddressEntity|OrderAddressEntity|null
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return CustomerAddressEntity|OrderAddressEntity|null
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    public function getCurrency(): ?CurrencyEntity
    {
        return $this->currency;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }
}
