<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalExpress;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\CheckoutDetailsStruct;

class SetCheckoutDetailsRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    /** @param CheckoutDetailsStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $currency  = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart      = $arguments->getCart();
        $returnUrl = $arguments->getReturnUrl();

        return [
            'request'             => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'clearingtype'        => self::CLEARING_TYPE_WALLET,
            'wallettype'          => 'PPE',
            'add_paydata[action]' => 'setexpresscheckout',
            'amount'              => $this->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency'            => $currency->getIsoCode(),
            'successurl'          => $returnUrl . '?state=success',
            'errorurl'            => $returnUrl . '?state=error',
            'backurl'             => $returnUrl . '?state=cancel',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof CheckoutDetailsStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaypalExpressPaymentHandler::class && $action === self::REQUEST_ACTION_SET_EXPRESS_CHECKOUT;
    }
}
