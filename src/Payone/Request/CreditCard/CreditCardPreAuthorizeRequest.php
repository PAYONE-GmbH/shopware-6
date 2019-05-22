<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class CreditCardPreAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    public function __construct(RedirectHandler $redirectHandler)
    {
        $this->redirectHandler = $redirectHandler;
    }

    /**
     * TODO: the reference number needs to be unique. When multiple transactions are possible per order, we need to add
     * TODO: a suffix/prefix or use another number as reference
     */
    public function getRequestParameters(PaymentTransactionStruct $transaction, string $pseudoPan, Context $context): array
    {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        return [
            'request'       => 'preauthorization',
            'clearingtype'  => 'cc',
            'amount'        => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'      => $transaction->getOrder()->getCurrency()->getIsoCode(),
            'reference'     => $transaction->getOrder()->getOrderNumber(),
            'pseudocardpan' => $pseudoPan,
            'successurl'    => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'      => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'       => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
        ];
    }
}
