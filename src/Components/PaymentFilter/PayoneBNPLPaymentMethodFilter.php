<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\Core\Utils\AddressCompare;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

class PayoneBNPLPaymentMethodFilter extends DefaultPaymentFilterService
{
    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        $billingAddress = $filterContext->getBillingAddress();
        $shippingAddress = $filterContext->getShippingAddress();

        if ($billingAddress instanceof OrderAddressEntity
            && $shippingAddress instanceof OrderAddressEntity
            && !AddressCompare::areOrderAddressesIdentical($billingAddress, $shippingAddress)) {
            throw new PaymentMethodNotAllowedException('Different billing and shipping addresses are not allowed for secured invoice');
        } elseif ($billingAddress instanceof CustomerAddressEntity
            && $shippingAddress instanceof CustomerAddressEntity
            && !AddressCompare::areCustomerAddressesIdentical($billingAddress, $shippingAddress)) {
            throw new PaymentMethodNotAllowedException('Different billing and shipping addresses are not allowed for secured invoice');
        }
    }
}
