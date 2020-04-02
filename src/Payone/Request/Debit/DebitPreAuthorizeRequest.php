<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

class DebitPreAuthorizeRequest extends AbstractDebitAuthorizeRequest
{
    public function getRequestParameters(PaymentTransaction $transaction, Context $context, string $iban, string $bic, string $accountOwner): array
    {
        return array_merge(parent::getRequestParameters($transaction, $context, $iban, $bic, $accountOwner), [
            'request' => 'preauthorization',
        ]);
    }
}
