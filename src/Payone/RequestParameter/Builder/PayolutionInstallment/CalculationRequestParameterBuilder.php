<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment;

use DateTime;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use Shopware\Core\Framework\Struct\Struct;

class CalculationRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    /** @param PayolutionAdditionalActionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $dataBag  = $arguments->getRequestData();
        $currency = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart     = $arguments->getCart();

        $parameters = [
            'request'             => 'genericpayment',
            'add_paydata[action]' => 'calculation',
            'clearingtype'        => 'fnc',
            'financingtype'       => 'PYS',
            'amount'              => $this->getConvertedAmount($cart->getPrice()->getTotalPrice(), $currency->getDecimalPrecision()),
            'currency'            => $currency->getIsoCode(),
            'workorderid'         => $arguments->getWorkorderId(),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return $parameters;
    }

    /** @param PayolutionAdditionalActionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PayolutionAdditionalActionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInstallmentPaymentHandler::class && $action === self::REQUEST_ACTION_PAYOLUTION_CALCULATION;
    }
}
