<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CartHasherInterface
{
    /**
     * @param Cart|OrderEntity $entity
     */
    public function generate(Struct $entity, SalesChannelContext $context): string;

    /**
     * @param Cart|OrderEntity $entity
     */
    public function validate(Struct $entity, string $cartHash, SalesChannelContext $context): bool;

    /**
     * @param AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransaction
     *
     * @throws AsyncPaymentProcessException|SyncPaymentProcessException
     */
    public function validateRequest(RequestDataBag $requestDataBag, $paymentTransaction, SalesChannelContext $salesChannelContext): void;

    /**
     * returns Criteria-Objects with all dependencies which are required to generate the full hash for an order
     */
    public function getCriteriaForOrder(?string $orderId = null): Criteria;
}
