<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use DateTime;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;

class PreCheckRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    /** @param PayolutionAdditionalActionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag  = $arguments->getRequestData();
        $currency = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart     = $arguments->getCart();

        $parameters = [
            'request'                   => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Invoicing',
            'clearingtype'              => self::CLEARING_TYPE_FINANCING,
            'financingtype'             => 'PYV',
            'amount'                    => $this->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency'                  => $currency->getIsoCode(),
            'workorderid'               => $arguments->getWorkorderId(),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PayolutionAdditionalActionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_PAYOLUTION_PRE_CHECK;
    }
}
