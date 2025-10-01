<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CustomerHasCompanyAddressTrait
{
    protected function customerHasCompanyAddress(SalesChannelContext $salesChannelContext): bool
    {
        $customer = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return false;
        }

        return !empty($billingAddress->getCompany());
    }
}
