<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Dto;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;

readonly class PaymentTransactionDto
{
    public function __construct(
        public OrderTransactionEntity $orderTransaction,
        public OrderEntity $order,
        public array $payoneTransactionData,
        public string|null $returnUrl = null,
    ) {
    }

    public static function createFromPaymentTransaction(
        PaymentTransactionStruct $paymentTransaction,
        OrderEntity $orderEntity,
        OrderTransactionEntity $orderTransaction,
    ): PaymentTransactionDto {
        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $orderTransaction
            ->getExtension(PayonePaymentOrderTransactionExtension::NAME)
        ;

        return new self(
            $orderTransaction,
            $orderEntity,
            null !== $transactionData ? $transactionData->jsonSerialize() : [],
            $paymentTransaction->getReturnUrl(),
        );
    }
}
