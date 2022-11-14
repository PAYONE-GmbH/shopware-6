<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\PaymentHandler\AbstractPostfinancePaymentHandler;
use PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class ReturnUrlRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    protected RedirectHandler $redirectHandler;

    public function __construct(
        RedirectHandler $redirectHandler
    ) {
        $this->redirectHandler = $redirectHandler;
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $paymentTransaction = $arguments->getPaymentTransaction();

        return [
            'successurl' => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=success'),
            'errorurl' => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=error'),
            'backurl' => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=cancel'),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        switch ($paymentMethod) {
            case PayonePaypalPaymentHandler::class:
            case PayonePaypalExpressPaymentHandler::class:
            case PayoneSofortBankingPaymentHandler::class:
            case PayoneCreditCardPaymentHandler::class:
            case PayoneTrustlyPaymentHandler::class:
            case PayoneEpsPaymentHandler::class:
            case PayoneIDealPaymentHandler::class:
            case PayoneBancontactPaymentHandler::class:
            case PayonePaydirektPaymentHandler::class:
            case PayonePrzelewy24PaymentHandler::class:
            case PayoneWeChatPayPaymentHandler::class:
            case PayoneAlipayPaymentHandler::class:
                return true;
        }

        if (is_subclass_of($paymentMethod, AbstractKlarnaPaymentHandler::class)) {
            return true;
        }

        if (is_subclass_of($paymentMethod, AbstractPostfinancePaymentHandler::class)) {
            return true;
        }

        return false;
    }

    protected function encodeUrl(string $url): string
    {
        return $this->redirectHandler->encode($url);
    }
}
