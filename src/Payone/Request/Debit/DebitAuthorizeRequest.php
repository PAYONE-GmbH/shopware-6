<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;

class DebitAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        string $iban,
        string $bic,
        string $accountOwner,
        Context $context
    ): array {
        return [
            'request'           => 'authorization',
            'clearingtype'      => 'elv',
            'iban'              => $iban,
            'bic'               => $bic,
            'bankaccountholder' => $accountOwner,
            'amount'            => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'          => $transaction->getOrder()->getCurrency()->getIsoCode(),
            'reference'         => $transaction->getOrder()->getOrderNumber(),
        ];
    }
}
