<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

class GenericExpressCheckoutFilterOther implements PaymentFilterServiceInterface
{
    /**
     * if AmazonPay/PayPal Express is selected, no other payment methods should be available.
     */
    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): PaymentMethodCollection
    {
        $actualPaymentMethod = $filterContext->getSalesChannelContext()->getPaymentMethod();

        if ($methodCollection->has($actualPaymentMethod->getId())
            && \in_array($actualPaymentMethod->getHandlerIdentifier(), PaymentHandlerGroups::GENERIC_EXPRESS, true)
        ) {
            return new PaymentMethodCollection([$actualPaymentMethod]);
        }

        return $methodCollection;
    }
}
