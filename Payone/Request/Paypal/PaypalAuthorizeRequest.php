<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\RequestInterface;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class PaypalAuthorizeRequest implements RequestInterface
{
    public function getParentRequest(): string
    {
        return CustomerRequest::class;
    }

    public function getRequestParameters($transaction, Context $context): array
    {
        $order = $transaction->getOrderTransaction()->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrderTransaction()->getOrderId());
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
