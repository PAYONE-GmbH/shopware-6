<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit;

use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();

        $parameters = [
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => 'PYD',
            'request' => self::REQUEST_ACTION_AUTHORIZE,
            'iban' => $dataBag->get('payolutionIban'),
            'bic' => $dataBag->get('payolutionBic'),
        ];

        $this->applyBirthdayParameter(
            $arguments->getPaymentTransaction()->getOrder(),
            $parameters,
            $dataBag,
            $arguments->getSalesChannelContext()->getContext()
        );

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayonePayolutionDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
