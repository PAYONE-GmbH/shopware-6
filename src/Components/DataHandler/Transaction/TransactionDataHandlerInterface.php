<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\Transaction;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

interface TransactionDataHandlerInterface
{
    public function getPaymentTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?PaymentTransaction;

    public function getCustomFieldsFromWebhook(PaymentTransaction $paymentTransaction, array $transactionData): array;

    public function saveTransactionData(PaymentTransaction $transaction, Context $context, array $data): void;

    public function logResponse(PaymentTransaction $transaction, Context $context, array $data): void;

    public function incrementSequenceNumber(PaymentTransaction $transaction, Context $context): void;

    public function saveTransactionState(string $stateId, PaymentTransaction $transaction, Context $context): void;
}
