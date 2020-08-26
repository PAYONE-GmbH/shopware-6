<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

trait RemovesPaymentMethod
{
    /**
     * Removes a payment method from the provided collection and
     * returns a filtered collection without the payment method.
     */
    protected function removePaymentMethod(PaymentMethodCollection $paymentMethods, string $paymentMethodId): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentMethodId) {
                return $paymentMethod->getId() !== $paymentMethodId;
            }
        );
    }
}
