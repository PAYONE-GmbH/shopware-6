<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\ExpressCheckout\ExpressCheckoutPaymentHandlerAwareInterface;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;

trait ExpressCheckoutPaymentMethodTrait
{
    protected PaymentHandlerInterface&ExpressCheckoutPaymentHandlerAwareInterface $paymentHandler;

    public function setPaymentHandler(
        PaymentHandlerInterface&ExpressCheckoutPaymentHandlerAwareInterface $paymentHandler
    ): void {
        $this->paymentHandler = $paymentHandler;
    }

    public function getPaymentHandler(): PaymentHandlerInterface&ExpressCheckoutPaymentHandlerAwareInterface
    {
        return $this->paymentHandler;
    }
}
