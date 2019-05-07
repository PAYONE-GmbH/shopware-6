<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface RefundPaymentHandlerInterface
{
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void;
}
