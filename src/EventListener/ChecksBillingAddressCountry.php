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

        if ($customer === null) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null || $billingAddress->getCountry() === null) {
            return false;
        }

        return $billingAddress->getCountry()->getIso() === $countryIso;
    }
}
