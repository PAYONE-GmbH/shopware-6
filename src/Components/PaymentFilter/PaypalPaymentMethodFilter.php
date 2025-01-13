<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayonePaypalV2;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaypalPaymentMethodFilter extends DefaultPaymentFilterService
{
    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        $paypalV1 = $methodCollection->get(PayonePaypal::UUID);
        $paypalV2 = $methodCollection->get(PayonePaypalV2::UUID);

        if ($paypalV1 instanceof PaymentMethodEntity && $paypalV2 instanceof PaymentMethodEntity) {
            throw new PaymentMethodNotAllowedException('PayPal: PayPal v1 is not allowed if v2 is active.');
        }
    }
}
