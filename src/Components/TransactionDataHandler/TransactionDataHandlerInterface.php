<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface TransactionDataHandlerInterface
{
    public function saveTransactionData(
        SalesChannelContext $context,
        PaymentTransactionStruct $transaction,
        array $data
    );

    public function logResponse(
        SalesChannelContext $context,
        PaymentTransactionStruct $transaction,
        array $response
    );
}
