<?php

declare(strict_types=1);

namespace PayonePayment\Components\CapturePaymentHandler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

interface CapturePaymentHandlerInterface
{
    public function captureTransaction(OrderTransactionEntity $orderTransaction, Context $context): void;
}
