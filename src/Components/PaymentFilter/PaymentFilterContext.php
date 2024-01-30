<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Core\Utils\AddressCompare;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentFilterContext extends Struct
{
    private bool $_areAddressesIdentical;

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

    public function areAddressesIdentical(): bool
    {
        if (isset($this->_areAddressesIdentical)) {
            return $this->_areAddressesIdentical;
        }

        $billingAddress = $this->getBillingAddress();
        $shippingAddress = $this->getShippingAddress();

        if ($billingAddress instanceof OrderAddressEntity
            && $shippingAddress instanceof OrderAddressEntity
            && $billingAddress->getId() !== $shippingAddress->getId()
            && !AddressCompare::areOrderAddressesIdentical($billingAddress, $shippingAddress)
        ) {
            return $this->_areAddressesIdentical = false;
        }

        if ($billingAddress instanceof CustomerAddressEntity
            && $shippingAddress instanceof CustomerAddressEntity
            && $billingAddress->getId() !== $shippingAddress->getId()
            && !AddressCompare::areCustomerAddressesIdentical($billingAddress, $shippingAddress)) {
            return $this->_areAddressesIdentical = false;
        }

        return $this->_areAddressesIdentical = true;
    }
}
