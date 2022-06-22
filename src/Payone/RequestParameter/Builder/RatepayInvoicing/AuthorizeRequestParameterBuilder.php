<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInvoicing;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\RatepayDebit\AuthorizeRequestParameterBuilder as RatepayDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends RatepayDebitAuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $order = $this->getOrder(
            $paymentTransaction->getOrder()->getId(),
            $salesChannelContext->getContext()
        );
        $profile = $this->getProfileByOrder($order, PayoneRatepayInvoicingPaymentHandler::class);

        $parameters = [
            'request'                                    => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype'                               => self::CLEARING_TYPE_FINANCING,
            'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPV,
            'telephonenumber'                            => $dataBag->get('ratepayPhone'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[shop_id]' => $profile['shopId'],
        ];

        $this->applyBirthdayParameter($parameters, $dataBag);

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneRatepayInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
