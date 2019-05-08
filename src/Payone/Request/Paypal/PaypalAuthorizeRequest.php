<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class PaypalAuthorizeRequest
{
    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $order = $transaction->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        return [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) ($order->getAmountTotal() * 100),
            'currency'     => $order->getCurrency()->getShortName(),
            'reference'    => $order->getOrderNumber(),
            'successurl'   => $transaction->getReturnUrl() . '&state=success',
            'errorurl'     => $transaction->getReturnUrl() . '&state=error',
            'backurl'      => $transaction->getReturnUrl() . '&state=cancel',
        ];
    }
}
