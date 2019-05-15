<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class CreditCardPreAuthorizeRequest
{
    public function getRequestParameters(PaymentTransactionStruct $transaction, string $pseudoPan, Context $context): array
    {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        $order = $transaction->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        return [
            'request'       => 'preauthorization',
            'clearingtype'  => 'cc',
            'amount'        => (int) ($order->getAmountTotal() * 100),
            'currency'      => $order->getCurrency()->getIsoCode(),
            'reference'     => $order->getOrderNumber(),
            'pseudocardpan' => $pseudoPan,
            'successurl'    => $transaction->getReturnUrl() . '&state=success',
            'errorurl'      => $transaction->getReturnUrl() . '&state=error',
            'backurl'       => $transaction->getReturnUrl() . '&state=cancel',
        ];
    }
}
