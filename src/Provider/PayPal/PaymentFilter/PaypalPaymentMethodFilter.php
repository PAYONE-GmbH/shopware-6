<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\PaymentFilter;

use PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\Provider\PayPal\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\PayPal\PaymentMethod\StandardV2PaymentMethod;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

readonly class PaypalPaymentMethodFilter extends DefaultPaymentFilterService
{
    /**
     * @throws PaymentMethodNotAllowedException
     */
    #[\Override]
    protected function additionalChecks(
        PaymentMethodCollection $methodCollection,
        PaymentFilterContext $filterContext,
    ): void {
        $paypalV1 = $methodCollection->get(StandardPaymentMethod::UUID);
        $paypalV2 = $methodCollection->get(StandardV2PaymentMethod::UUID);

        if ($paypalV1 instanceof PaymentMethodEntity && $paypalV2 instanceof PaymentMethodEntity) {
            throw new PaymentMethodNotAllowedException('PayPal: PayPal v1 is not allowed if v2 is active.');
        }
    }

    protected function validateDifferentShippingAddress(
        PaymentMethodCollection $paymentMethods,
        PaymentFilterContext $filterContext,
    ): void {
        // Not required for Paypal
    }
}
