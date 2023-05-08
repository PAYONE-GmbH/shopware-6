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
    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly CustomerAddressEntity|OrderAddressEntity|null $billingAddress = null,
        private readonly CustomerAddressEntity|OrderAddressEntity|null $shippingAddress = null,
        private readonly ?CurrencyEntity $currency = null,
        private readonly ?OrderEntity $order = null,
        private readonly ?Cart $cart = null
    ) {
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getBillingAddress(): CustomerAddressEntity|OrderAddressEntity|null
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): CustomerAddressEntity|OrderAddressEntity|null
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
