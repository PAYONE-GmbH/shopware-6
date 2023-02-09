<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Core\Utils\AddressCompare;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

class PayoneBNPLPaymentMethodFilter extends DefaultPaymentFilterService
{
    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): PaymentMethodCollection
    {
        $methodCollection = parent::filterPaymentMethods($methodCollection, $filterContext);

        $billingAddress = $filterContext->getBillingAddress();
        $shippingAddress = $filterContext->getShippingAddress();

        // Different billing and shipping addresses are not allowed for secured invoice
        if ($billingAddress instanceof OrderAddressEntity
            && $shippingAddress instanceof OrderAddressEntity
            && !AddressCompare::areOrderAddressesIdentical($billingAddress, $shippingAddress)) {
            $methodCollection = $this->removePaymentMethod($methodCollection);
        } elseif ($billingAddress instanceof CustomerAddressEntity
            && $shippingAddress instanceof CustomerAddressEntity
            && !AddressCompare::areCustomerAddressesIdentical($billingAddress, $shippingAddress)) {
            $methodCollection = $this->removePaymentMethod($methodCollection);
        }

        return $methodCollection;
    }
}
