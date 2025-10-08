<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class TotalPriceFilter implements PaymentFilterServiceInterface
{
    #[\Override]
    public function filterPaymentMethods(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
        if ($filterContext->getOrder()) {
            $price = $filterContext->getOrder()->getPrice()->getTotalPrice();
        } elseif ($filterContext->getCart()?->getLineItems()->count() > 0) {
            $price = $filterContext->getCart()->getPrice()->getTotalPrice();
        } else {
            return;
        }

        if ($price <= 0) {
            $idsToRemove = $methodCollection->filter(
                static fn (PaymentMethodEntity $entity) => \is_subclass_of(
                    $entity->getHandlerIdentifier(),
                    AbstractPaymentHandler::class,
                ),
            )->getIds();

            foreach ($idsToRemove as $id) {
                $methodCollection->remove($id);
            }
        }
    }
}
