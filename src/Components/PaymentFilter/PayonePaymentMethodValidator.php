<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class PayonePaymentMethodValidator implements CartValidatorInterface
{
    public function __construct(
        private IterablePaymentFilter $iterablePaymentFilter,
        private PaymentFilterContextFactoryInterface $paymentFilterContextFactory,
    ) {
    }

    /**
     * This validation takes care of changing the currently selected PAYONE payment method if it is no longer available.
     */
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (0 === $cart->getLineItems()->count()) {
            // we do not need to validate a cart, which does not contain any line-items
            return;
        }

        $paymentMethod  = $context->getPaymentMethod();
        $paymentMethods = new PaymentMethodCollection([$paymentMethod]);
        $filterContext  = $this->paymentFilterContextFactory->createContextForCart($cart, $context);

        $this->iterablePaymentFilter->filterPaymentMethods($paymentMethods, $filterContext);

        if (0 === $paymentMethods->count()) {
            $errors->add(
                new PaymentMethodBlockedError((string) $paymentMethod->getTranslation('name')),
            );
        }
    }
}
