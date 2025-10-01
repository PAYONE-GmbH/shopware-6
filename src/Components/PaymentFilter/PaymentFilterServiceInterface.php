<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

interface PaymentFilterServiceInterface
{
    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void;
}
