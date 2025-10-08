<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

readonly class IterablePaymentFilter implements PaymentFilterServiceInterface
{
    /**
     * @param iterable<PaymentFilterServiceInterface> $services
     */
    public function __construct(
        private iterable $services,
    ) {
    }

    #[\Override]
    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
        foreach ($this->services as $service) {
            $service->filterPaymentMethods($methodCollection, $filterContext);
        }
    }
}
