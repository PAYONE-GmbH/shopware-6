<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;

class PayoneCreditCardPaymentHandler implements SynchronousPaymentHandlerInterface
{
    public function pay(SyncPaymentTransactionStruct $transaction, Context $context): void
    {
        // TODO call payone and save card data
    }
}
