<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;

interface RequestInterface
{
    public function getParentRequest(): string;

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array;
}
