<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use DateTime;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit\AuthorizeRequestParameterBuilder as PayolutionDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class AuthorizeRequestParameterBuilder extends PayolutionDebitAuthorizeRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();

        $parameters = [
            'clearingtype'  => 'fnc',
            'financingtype' => 'PYV',
            'request'       => 'authorization',
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
        }

        return $parameters;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
