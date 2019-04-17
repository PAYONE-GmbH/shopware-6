<?php

declare(strict_types=1);

namespace PayonePayment\Refund;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface RefundHandlerInterface
{
    public function refundTransaction(OrderTransactionEntity $transaction, Context $context): void;
}
