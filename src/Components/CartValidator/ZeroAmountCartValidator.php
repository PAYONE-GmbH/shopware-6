<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartValidator;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Installer\PaymentMethodInstaller;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ZeroAmountCartValidator implements CartValidatorInterface
{
    protected CurrencyPrecisionInterface $currencyPrecision;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->currencyPrecision = $currencyPrecision;
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if ($cart->getLineItems()->count() === 0) {
            return;
        }

        if ($this->currencyPrecision->getRoundedItemAmount($cart->getPrice()->getTotalPrice(), $context->getCurrency()) > 0) {
            return;
        }

        if (mb_strpos($context->getPaymentMethod()->getHandlerIdentifier(), PaymentMethodInstaller::HANDLER_IDENTIFIER_ROOT_NAMESPACE) === false) {
            return;
        }

        $errors->add(
            new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name'))
        );
    }
}
