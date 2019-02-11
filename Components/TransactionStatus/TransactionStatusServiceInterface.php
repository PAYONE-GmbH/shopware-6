<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;

interface TransactionStatusServiceInterface
{
    /**
     * Persists the provided TransactionStatusStruct into the database.
     *
     * @param TransactionStatusStruct $status
     */
    public function persistTransactionStatus(TransactionStatusStruct $status);
}
