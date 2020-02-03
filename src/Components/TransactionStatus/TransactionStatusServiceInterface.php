<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface TransactionStatusServiceInterface
{
    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, OrderTransactionEntity $orderTransactionEntity, array $transactionData): void;

    public function transitionByName(Context $context, string $transactionId, string $transitionName): void;
}
