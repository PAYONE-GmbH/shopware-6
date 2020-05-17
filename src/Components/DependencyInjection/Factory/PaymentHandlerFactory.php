<?php

declare(strict_types=1);

namespace PayonePayment\Components\DependencyInjection\Factory;

use PayonePayment\Components\Exception\NoPaymentHandlerFoundException;
use PayonePayment\PaymentHandler\PayonePaymentHandlerInterface;

class PaymentHandlerFactory
{
    /** @var PayonePaymentHandlerInterface[] */
    protected $paymentHandlerCollection = [];

    public function getPaymentHandler(string $paymentMethodId, string $orderNumber): PayonePaymentHandlerInterface
    {
        foreach ($this->paymentHandlerCollection as $statusMapper) {
            if (!empty($paymentMethodId) && $statusMapper->supports($paymentMethodId)) {
                return $statusMapper;
            }
        }

        throw new NoPaymentHandlerFoundException($orderNumber);
    }

    public function addPaymentHandler(PayonePaymentHandlerInterface $statusMapper): void
    {
        $this->paymentHandlerCollection[] = $statusMapper;
    }
}
