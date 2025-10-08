<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

readonly class FilterNoLongerSupportedPaymentMethods implements PaymentFilterServiceInterface
{
    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
        foreach ($methodCollection as $id => $entity) {
            $paymentMethod = $this->paymentMethodRegistry->getById($entity->getId());

            if (null === $paymentMethod) {
                continue;
            }

            if ($paymentMethod instanceof NoLongerSupportedPaymentMethodInterface) {
                $methodCollection->remove($id);
            }
        }
    }
}
