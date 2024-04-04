<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CartHasherInterface
{
    public function generate(Cart|OrderEntity $entity, SalesChannelContext $context): string;

    public function validate(Cart|OrderEntity $entity, string $cartHash, SalesChannelContext $context): bool;

    public function validateRequest(
        RequestDataBag $requestDataBag,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext
    ): void;

    /**
     * returns Criteria-Objects with all dependencies which are required to generate the full hash for an order
     */
    public function getCriteriaForOrder(?string $orderId = null): Criteria;
}
