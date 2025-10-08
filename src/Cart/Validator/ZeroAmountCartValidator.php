<?php

declare(strict_types=1);

namespace PayonePayment\Cart\Validator;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Service\CurrencyPrecisionService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class ZeroAmountCartValidator implements CartValidatorInterface
{
    public function __construct(
        private CurrencyPrecisionService $currencyPrecision,
    ) {
    }

    #[\Override]
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (0 === $cart->getLineItems()->count()) {
            return;
        }

        $roundedTotalPrice = $this->currencyPrecision->getRoundedItemAmount(
            $cart->getPrice()->getTotalPrice(),
            $context->getCurrency(),
        );

        if (0 < $roundedTotalPrice) {
            return;
        }

        if (
            !\str_contains(
                $context->getPaymentMethod()->getHandlerIdentifier(),
                PaymentMethodInstaller::HANDLER_IDENTIFIER_ROOT_NAMESPACE,
            )
        ) {
            return;
        }

        $errors->add(
            new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name')),
        );
    }
}
