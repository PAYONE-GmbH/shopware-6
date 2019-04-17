<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Struct;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;

class PaymentTransactionStruct
{
    /** @var OrderTransactionEntity */
    private $orderTransaction;

    /** @var OrderEntity */
    private $order;

    /** @var null|string */
    private $returnUrl;

    public function __construct(OrderTransactionEntity $orderTransaction, OrderEntity $order, ?string $returnUrl = null)
    {
        $this->orderTransaction = $orderTransaction;
        $this->order            = $order;
        $this->returnUrl        = $returnUrl;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public static function fromOrderTransaction(OrderTransactionEntity $struct): self
    {
        return new self(
            $struct,
            $struct->getOrder()
        );
    }

    public static function fromAsyncPaymentTransactionStruct(AsyncPaymentTransactionStruct $struct): self
    {
        return new self(
            $struct->getOrderTransaction(),
            $struct->getOrder(),
            $struct->getReturnUrl()
        );
    }

    public static function fromSyncPaymentTransactionStruct(SyncPaymentTransactionStruct $struct): self
    {
        return new self(
            $struct->getOrderTransaction(),
            $struct->getOrder()
        );
    }
}
