<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface TransactionStatusServiceInterface
{
    public function transitionByConfigMapping(SalesChannelContext $salesChannelContext, PaymentTransaction $paymentTransaction, array $transactionData): void;

    public function transitionByName(Context $context, string $transactionId, string $transitionName, array $parameter = []): void;
}
