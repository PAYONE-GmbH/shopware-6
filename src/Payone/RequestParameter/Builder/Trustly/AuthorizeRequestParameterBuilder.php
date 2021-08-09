<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Trustly;

use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag            = $arguments->getRequestData();
        $paymentTransaction = $arguments->getPaymentTransaction();
        $iban               = $this->validateIbanRequestParameter($dataBag, $paymentTransaction);

        return [
            'clearingtype'           => self::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
            'onlinebanktransfertype' => 'TRL',
            'iban'                   => $iban,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneTrustlyPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    private function validateIbanRequestParameter(ParameterBag $dataBag, PaymentTransaction $transaction): string
    {
        $iban = $dataBag->get('iban');

        if (empty($iban) || !is_string($iban)) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Missing iban parameter.');
        }

        return $iban;
    }
}
