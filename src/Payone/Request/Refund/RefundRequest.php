<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Refund;

use PayonePayment\Payone\Request\RequestInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class RefundRequest implements RequestInterface
{
    public function getParentRequest(): string
    {
        return SystemRequest::class;
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        $order = $transaction->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrderTransaction()->getOrderId());
        }

        return [
            'request'        => 'refund',
            'txid'           => $transaction->getOrderTransaction()->getAttributes(),
            'sequencenumber' => 'wlt',
            'amount'         => (int) ($order->getAmountTotal() * 100),
            'currency'       => $order->getCurrency()->getShortName(),
        ];
    }
}
