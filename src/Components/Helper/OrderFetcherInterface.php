<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface OrderFetcherInterface
{
    public function getOrderFromOrderAddress(string $orderAddressId, Context $context): ?OrderEntity;

    public function getOrderFromOrderLineItem(string $lineItemId, Context $context): ?OrderEntity;

    public function getOrderFromOrder(string $orderId, Context $context): ?OrderEntity;

    public function getOrderFromOrderTransaction(string $transactionId, Context $context): ?OrderEntity;

    public function getOrderFromOrderDelivery(string $deliveryId, Context $context): ?OrderEntity;
}
