<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class ReturnUrlRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var RedirectHandler */
    protected $redirectHandler;

    public function __construct(
        RedirectHandler $redirectHandler
    ) {
        $this->redirectHandler = $redirectHandler;
    }

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

        if ($paymentMethod === PayonePaypalExpressPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayoneSofortBankingPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayoneCreditCardPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayoneTrustlyPaymentHandler::class) {
            return true;
        }

        return false;
    }

    protected function encodeUrl(string $url): string
    {
        return $this->redirectHandler->encode($url);
    }
}
