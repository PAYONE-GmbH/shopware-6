<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SofortBanking;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

class SofortBankingAuthorizeRequest extends AbstractSofortBankingAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $referenceNumber
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $context, $referenceNumber), [
            'request' => 'authorization',
        ]);
    }
}
