<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ChecksBillingAddressCountry
{
    /**
     * Checks that the customers billing address (if any given)
     * matches the provided country ISO code.
     */
    protected function isBillingAddressFromCountry(SalesChannelContext $context, string $countryIso): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return false;
        }

        return $billingAddress->getCountry()->getIso() === $countryIso;
    }
}
