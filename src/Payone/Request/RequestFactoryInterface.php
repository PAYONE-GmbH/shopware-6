<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface RequestFactoryInterface
{
    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array;
}
