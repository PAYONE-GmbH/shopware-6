<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\RatepayDebit\AuthorizeRequestParameterBuilder as RatepayDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends RatepayDebitAuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();

        $parameters = [
            'request'                                    => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype'                               => self::CLEARING_TYPE_FINANCING,
            'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
            'iban'                                       => $dataBag->get('ratepayIban'),
            'telephonenumber'                            => $dataBag->get('ratepayPhone'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[debit_paytype]'                 => 'DIRECT-DEBIT', // ToDo: DIRECT-DEBIT or BANK-TRANSFER ?
            'add_paydata[installment_amount]'            => $dataBag->get('ratepayInstallmentAmount'),
            'add_paydata[installment_number]'            => $dataBag->get('ratepayInstallmentNumber'),
            'add_paydata[last_installment_amount]'       => $dataBag->get('ratepayLastInstallmentAmount'),
            'add_paydata[interest_rate]'                 => $dataBag->get('ratepayInterestRate'),
            'add_paydata[amount]'                        => $dataBag->get('ratepayTotalAmount'),

            // ToDo: Ratepay Profile in der Administration pflegbar machen
            'add_paydata[shop_id]' => 88880103,
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

        return $paymentMethod === PayoneRatepayInstallmentPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
