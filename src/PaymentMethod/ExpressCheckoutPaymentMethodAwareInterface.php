<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\ExpressCheckout\ExpressCheckoutPaymentHandlerAwareInterface;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;

interface ExpressCheckoutPaymentMethodAwareInterface
{
    public function setPaymentHandler(
        PaymentHandlerInterface&ExpressCheckoutPaymentHandlerAwareInterface $paymentHandler,
    ): void;

    public function getPaymentHandler(): PaymentHandlerInterface&ExpressCheckoutPaymentHandlerAwareInterface;
}
