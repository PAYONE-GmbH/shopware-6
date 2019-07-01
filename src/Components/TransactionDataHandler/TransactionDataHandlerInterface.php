<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

interface TransactionDataHandlerInterface
{
    public function saveTransactionData(PaymentTransaction $transaction, Context $context, array $data): void;

    public function logResponse(PaymentTransaction $transaction, Context $context, array $response): void;

    public function incrementSequenceNumber(PaymentTransaction $transaction, Context $context): void;

    public function setState(PaymentTransaction $transaction, Context $context, StateMachineStateEntity $state): void;
}
