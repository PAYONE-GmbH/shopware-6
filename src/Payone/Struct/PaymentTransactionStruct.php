<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Struct;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;

class PaymentTransactionStruct
{
    /** @var ?OrderTransactionEntity */
    private $orderTransaction;

    /** @var ?OrderEntity */
    private $order;

    /** @var array */
    private $customFields;

    /** @var null|string */
    private $returnUrl;

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getOrderTransaction(): ?OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public static function fromOrderTransaction(OrderTransactionEntity $transaction): self
    {
        $transactionStruct = new self();
        $transactionStruct->order = $transaction->getOrder();
        $transactionStruct->customFields = $transaction->getCustomFields() ?? [];
        $transactionStruct->orderTransaction = $transaction;

        return $transactionStruct;
    }

    public static function fromAsyncPaymentTransactionStruct(AsyncPaymentTransactionStruct $struct): self
    {
        $transactionStruct = new self();
        $transactionStruct->order = $struct->getOrder();
        $transactionStruct->customFields = $struct->getOrderTransaction()->getCustomFields() ?? [];
        $transactionStruct->orderTransaction = $struct->getOrderTransaction();
        $transactionStruct->returnUrl = $struct->getReturnUrl();

        return $transactionStruct;
    }

    public static function fromSyncPaymentTransactionStruct(SyncPaymentTransactionStruct $struct): self
    {
        $transactionStruct = new self();
        $transactionStruct->order = $struct->getOrder();
        $transactionStruct->customFields = $struct->getOrderTransaction()->getCustomFields() ?? [];
        $transactionStruct->orderTransaction = $struct->getOrderTransaction();

        return $transactionStruct;
    }
}
