<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment;

use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit\AuthorizeRequestParameterBuilder as PayolutionDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends PayolutionDebitAuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();

        $parameters = [
            'clearingtype'                      => self::CLEARING_TYPE_FINANCING,
            'financingtype'                     => 'PYS',
            'request'                           => self::REQUEST_ACTION_AUTHORIZE,
            'add_paydata[installment_duration]' => (int) $dataBag->get('payolutionInstallmentDuration'),
            'iban'                              => $dataBag->get('payolutionIban'),
            'bic'                               => $dataBag->get('payolutionBic'),
            'bankaccountholder'                 => $dataBag->get('payolutionAccountOwner'),
        ];

        $this->applyBirthdayParameter($parameters, $dataBag);

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInstallmentPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
