<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class PaymentTransaction extends Struct
{
    /** @var OrderTransactionEntity */
    protected $orderTransaction;

    /** @var OrderEntity */
    protected $order;

    /** @var array */
    protected $payoneTransactionData;

    /** @var null|string */
    protected $returnUrl;

    public function getPayoneTransactionData(): array
    {
        return $this->payoneTransactionData;
    }

    public function setPayoneTransactionData(array $payoneTransactionData): void
    {
        $this->payoneTransactionData = $payoneTransactionData;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public static function fromOrderTransaction(OrderTransactionEntity $transaction, OrderEntity $orderEntity): self
    {
        $transactionStruct        = new self();
        $transactionStruct->order = $orderEntity;

        /** @var null|PayonePaymentOrderTransactionDataEntity $transactionData */
        $transactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        $transactionStruct->payoneTransactionData = $transactionData !== null ? $transactionData->jsonSerialize() : [];
        $transactionStruct->orderTransaction      = $transaction;

        return $transactionStruct;
    }

    public static function fromAsyncPaymentTransactionStruct(AsyncPaymentTransactionStruct $struct, OrderEntity $orderEntity): self
    {
        $transactionStruct        = new self();
        $transactionStruct->order = $orderEntity;

        /** @var null|PayonePaymentOrderTransactionDataEntity $transactionData */
        $transactionData = $struct->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        $transactionStruct->payoneTransactionData = $transactionData !== null ? $transactionData->jsonSerialize() : [];
        $transactionStruct->orderTransaction      = $struct->getOrderTransaction();
        $transactionStruct->returnUrl             = $struct->getReturnUrl();

        return $transactionStruct;
    }

    public static function fromSyncPaymentTransactionStruct(SyncPaymentTransactionStruct $struct, OrderEntity $orderEntity): self
    {
        $transactionStruct        = new self();
        $transactionStruct->order = $orderEntity;

        /** @var null|PayonePaymentOrderTransactionDataEntity $transactionData */
        $transactionData = $struct->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        $transactionStruct->payoneTransactionData = $transactionData !== null ? $transactionData->jsonSerialize() : [];
        $transactionStruct->orderTransaction      = $struct->getOrderTransaction();

        return $transactionStruct;
    }
}
