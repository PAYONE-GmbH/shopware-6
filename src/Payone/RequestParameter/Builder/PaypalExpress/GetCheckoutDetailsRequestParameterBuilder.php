<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PaypalExpress;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\CheckoutDetailsStruct;
use Shopware\Core\Framework\Struct\Struct;

class GetCheckoutDetailsRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    public function getRequestParameter(
        Struct $arguments
    ): array {
        /** @var CheckoutDetailsStruct $arguments */
        $currency = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart     = $arguments->getCart();

        return [
            'request'             => 'genericpayment',
            'clearingtype'        => 'wlt',
            'wallettype'          => 'PPE',
            'add_paydata[action]' => 'getexpresscheckoutdetails',
            'amount'              => $this->getConvertedAmount($cart->getPrice()->getTotalPrice(), $currency->getDecimalPrecision()),
            'currency'            => $currency->getIsoCode(),
            'workorderid'         => $arguments->getWorkorderId(),
        ];
    }

    /** @param Struct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof CheckoutDetailsStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaypalExpressPaymentHandler::class && $action === self::REQUEST_ACTION_GET_EXPRESS_CHECKOUT_DETAILS;
    }
}
