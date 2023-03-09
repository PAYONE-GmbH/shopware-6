<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit;

use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Symfony\Component\HttpFoundation\ParameterBag;

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

        $this->applyBirthdayParameterWithoutCustomField($parameters, $dataBag);

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

    protected function applyBirthdayParameterWithoutCustomField(array &$parameters, ParameterBag $dataBag): void
    {
        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }
    }
}
