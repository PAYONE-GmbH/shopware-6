<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class ReturnUrlRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction = $arguments->getPaymentTransaction();

        return [
            'successurl' => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=success'),
            'errorurl'   => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=error'),
            'backurl'    => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=cancel'),
        ];
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        if ($paymentMethod === PayonePaypalPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayoneSofortBankingPaymentHandler::class) {
            return true;
        }

        return false;
    }
}
