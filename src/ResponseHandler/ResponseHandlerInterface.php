<?php

declare(strict_types=1);

namespace PayonePayment\ResponseHandler;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ResponseHandlerInterface
{
    public function handle(
        string $orderTransactionId,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext,
    ): void;
}
