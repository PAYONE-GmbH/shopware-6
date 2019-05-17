<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;

class DebitAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    public function __construct(RedirectHandler $redirectHandler)
    {
        $this->redirectHandler = $redirectHandler;
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        return [
            'request'           => 'authorization',
            'clearingtype'      => 'elv',
            'iban'              => 'DE00123456782599100003',
            'bic'               => 'TESTTEST',
            'bankaccountholder' => 'test',
            'amount'            => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'          => $transaction->getOrder()->getCurrency()->getIsoCode(),
            'reference'         => $transaction->getOrder()->getOrderNumber(),
        ];
    }
}
