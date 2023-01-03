<?php declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayonePaymentMethodValidator implements CartValidatorInterface
{
    private IterablePaymentFilter $iterablePaymentFilter;

    private PaymentFilterContextFactoryInterface $paymentFilterContextFactory;

    public function __construct(
        IterablePaymentFilter $iterablePaymentFilter,
        PaymentFilterContextFactoryInterface $paymentFilterContextFactory
    ) {
        $this->iterablePaymentFilter = $iterablePaymentFilter;
        $this->paymentFilterContextFactory = $paymentFilterContextFactory;
    }

    /**
     * This validation takes care of changing the currently selected PAYONE payment method if it is no longer available.
     */
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        $paymentMethod = $context->getPaymentMethod();
        $paymentMethods = new PaymentMethodCollection([$paymentMethod]);

        $filterContext = $this->paymentFilterContextFactory->createContextForCart($cart, $context);

        $paymentMethods = $this->iterablePaymentFilter->filterPaymentMethods($paymentMethods, $filterContext);

        if ($paymentMethods->count() === 0) {
            $errors->add(
                new PaymentMethodBlockedError((string) $paymentMethod->getTranslation('name'))
            );
        }
    }
}
