<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @deprecated replace with \PayonePayment\RequestParameter\PaymentRequestDto
 */
class PaymentTransaction extends Struct
{
    protected OrderTransactionEntity $orderTransaction;

    protected OrderEntity $order;

    protected array $payoneTransactionData;

    protected string|null $returnUrl = null;

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

        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        $transactionStruct->payoneTransactionData = null !== $transactionData ? $transactionData->jsonSerialize() : [];
        $transactionStruct->orderTransaction      = $transaction;

        return $transactionStruct;
    }
}
