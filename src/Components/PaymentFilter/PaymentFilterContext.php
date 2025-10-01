<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Service\AddressCompareService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentFilterContext extends Struct
{
    final public const FLAG_SKIP_EC_REQUIRED_DATA_VALIDATION = 'skip_express_checkout_required_data_validation';

    private bool $areAddressesIdentical;

    /**
     * @param string[] $flags
     */
    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly AddressCompareService $addressCompareService,
        private readonly CustomerAddressEntity|OrderAddressEntity|null $billingAddress = null,
        private readonly CustomerAddressEntity|OrderAddressEntity|null $shippingAddress = null,
        private readonly CurrencyEntity|null $currency = null,
        private readonly OrderEntity|null $order = null,
        private readonly Cart|null $cart = null,
        private readonly array $flags = [],
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

    public function getCurrency(): CurrencyEntity|null
    {
        return $this->currency;
    }

    public function getOrder(): OrderEntity|null
    {
        return $this->order;
    }

    public function getCart(): Cart|null
    {
        return $this->cart;
    }

    public function areAddressesIdentical(): bool
    {
        if (isset($this->areAddressesIdentical)) {
            return $this->areAddressesIdentical;
        }

        $billingAddress  = $this->getBillingAddress();
        $shippingAddress = $this->getShippingAddress();

        if (
            $billingAddress instanceof OrderAddressEntity
            && $shippingAddress instanceof OrderAddressEntity
            && !$this->addressCompareService->areOrderAddressesIdentical($billingAddress, $shippingAddress)
            && $billingAddress->getId() !== $shippingAddress->getId()
        ) {
            return $this->areAddressesIdentical = false;
        }

        if (
            $billingAddress instanceof CustomerAddressEntity
            && $shippingAddress instanceof CustomerAddressEntity
            && !$this->addressCompareService->areCustomerAddressesIdentical($billingAddress, $shippingAddress)
            && $billingAddress->getId() !== $shippingAddress->getId()
        ) {
            return $this->areAddressesIdentical = false;
        }

        return $this->areAddressesIdentical = true;
    }

    public function hasFlag(string $key): bool
    {
        return \in_array($key, $this->flags, true);
    }
}
