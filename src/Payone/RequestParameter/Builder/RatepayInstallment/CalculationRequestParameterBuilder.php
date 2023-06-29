<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;

class CalculationRequestParameterBuilder extends GeneralTransactionRequestParameterBuilder
{
    final public const INSTALLMENT_TYPE_RATE = 'rate';
    final public const INSTALLMENT_TYPE_TIME = 'time';

    /**
     * @param RatepayCalculationStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $currency = $this->getOrderCurrency(null, $arguments->getSalesChannelContext()->getContext());
        $cart = $arguments->getCart();
        $profile = $arguments->getProfile();
        $installmentType = $dataBag->get('ratepayInstallmentType');

        $parameters = [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'calculation',
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
            'amount' => $this->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),
            'currency' => $currency->getIsoCode(),
            'add_paydata[shop_id]' => $profile->getShopId(),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
        ];

        if ($installmentType === self::INSTALLMENT_TYPE_RATE) {
            $parameters['add_paydata[calculation_type]'] = 'calculation-by-rate';
            $parameters['add_paydata[rate]'] = $this->currencyPrecision->getRoundedTotalAmount(
                (float) $dataBag->get('ratepayInstallmentValue'),
                $currency
            );
        } elseif ($installmentType === self::INSTALLMENT_TYPE_TIME) {
            $parameters['add_paydata[calculation_type]'] = 'calculation-by-time';
            $parameters['add_paydata[month]'] = $dataBag->get('ratepayInstallmentValue');
        } else {
            throw new \RuntimeException('invalid installment type');
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof RatepayCalculationStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneRatepayInstallmentPaymentHandler::class && $action === self::REQUEST_ACTION_RATEPAY_CALCULATION;
    }
}
