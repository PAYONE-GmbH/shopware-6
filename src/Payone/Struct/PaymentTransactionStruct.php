<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Struct;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;

class PaymentTransactionStruct
{
    /** @var array */
    private $customFields;

    /** @var OrderEntity */
    private $order;

    /** @var null|string */
    private $returnUrl;

    public function __construct(OrderEntity $order, array $customFields = [], ?string $returnUrl = null)
    {
        $this->customFields = $customFields;
        $this->order        = $order;
        $this->returnUrl    = $returnUrl;
    }

    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public static function fromOrder(OrderEntity $order): self
    {
        return new self(
            $order
        );
    }

    public static function fromOrderTransaction(OrderTransactionEntity $struct): self
    {
        return new self(
            $struct->getOrder(),
            $struct->getCustomFields() ?? []
        );
    }

    public static function fromAsyncPaymentTransactionStruct(AsyncPaymentTransactionStruct $struct): self
    {
        return new self(
            $struct->getOrder(),
            $struct->getOrderTransaction()->getCustomFields() ?? [],
            $struct->getReturnUrl()
        );
    }

    public static function fromSyncPaymentTransactionStruct(SyncPaymentTransactionStruct $struct): self
    {
        return new self(
            $struct->getOrder(),
            $struct->getOrderTransaction()->getCustomFields() ?? []
        );
    }
}
