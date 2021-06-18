<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Trustly;

use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $dataBag            = $arguments->getRequestData();
        $paymentTransaction = $arguments->getPaymentTransaction();
        $iban               = $this->validateIbanRequestParameter($dataBag, $paymentTransaction);

        return [
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'TRL',
            'iban'                   => $iban,
        ];
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneTrustlyPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    private function validateIbanRequestParameter(RequestDataBag $dataBag, PaymentTransaction $transaction): string
    {
        $iban = $dataBag->get('iban');

        if (empty($iban) || !is_string($iban)) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Missing iban parameter.');
        }

        return $iban;
    }
}
