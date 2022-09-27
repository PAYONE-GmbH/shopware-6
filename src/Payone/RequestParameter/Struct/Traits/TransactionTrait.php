<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

use PayonePayment\Struct\PaymentTransaction;

trait TransactionTrait
{
    protected PaymentTransaction $paymentTransaction;

    public function getPaymentTransaction(): PaymentTransaction
    {
        return $this->paymentTransaction;
    }
}
