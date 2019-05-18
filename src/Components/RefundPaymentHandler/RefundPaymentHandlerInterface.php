<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

interface RefundPaymentHandlerInterface
{
    /**
     * @param OrderTransactionEntity $orderTransaction
     * @param Context $context
     *
     * @throws InvalidOrderException
     * @throws PayoneRequestException
     */
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void;
}
