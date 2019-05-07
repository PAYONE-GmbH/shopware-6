<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface TransactionStatusServiceInterface
{
    /**
     * Persists the provided TransactionStatusStruct into the database.
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array               $transactionData
     */
    public function persistTransactionStatus(SalesChannelContext $salesChannelContext, array $transactionData): void;
}
