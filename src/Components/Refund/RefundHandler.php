<?php

declare(strict_types=1);

namespace PayonePayment\Refund;

use PayonePayment\Payone\Request\Refund\RefundRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

class RefundHandler implements RefundHandlerInterface
{
    public function refundTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromOrderTransaction($transaction);

        $request = $this->requestFactory->generateRequest(
            $paymentTransaction,
            $context,
            RefundRequest::class
        );

        $response = $this->client->request($request);

        var_dump($response);
    }
}
