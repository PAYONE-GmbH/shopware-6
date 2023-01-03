<?php declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

class IterablePaymentFilter implements PaymentFilterServiceInterface
{
    private iterable $services;

    /**
     * @param iterable<PaymentFilterServiceInterface> $services
     */
    public function __construct(iterable $services)
    {
        $this->services = $services;
    }

    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext
    ): PaymentMethodCollection {
        foreach ($this->services as $service) {
            $methodCollection = $service->filterPaymentMethods($methodCollection, $filterContext);
        }

        return $methodCollection;
    }
}
