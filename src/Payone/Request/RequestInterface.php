<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;

interface RequestInterface
{
    public function getParentRequest(): string;

    /**
     * @param AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $transaction
     * @param Context                                                    $context
     *
     * @return array
     */
    public function getRequestParameters($transaction, Context $context): array;
}
