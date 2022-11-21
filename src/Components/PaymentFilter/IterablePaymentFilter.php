<?php declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Storefront\Page\PageLoadedEvent;

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

    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, string $currencyIso, $billingAddress = null, $shippingAddress = null): PaymentMethodCollection
    {
        foreach ($this->services as $service) {
            $methodCollection = $service->filterPaymentMethods($methodCollection, $currencyIso, $billingAddress, $shippingAddress);
        }

        return $methodCollection;
    }

    public function filterPaymentMethodsAdditionalCheck(PaymentMethodCollection $methodCollection, PageLoadedEvent $event): PaymentMethodCollection
    {
        foreach ($this->services as $service) {
            $methodCollection = $service->filterPaymentMethodsAdditionalCheck($methodCollection, $event);
        }

        return $methodCollection;
    }
}
