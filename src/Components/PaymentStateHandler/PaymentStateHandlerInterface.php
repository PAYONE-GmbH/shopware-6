<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentStateHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;

interface PaymentStateHandlerInterface
{
    public function handleStateResponse(AsyncPaymentTransactionStruct $transaction, string $state): void;
}
