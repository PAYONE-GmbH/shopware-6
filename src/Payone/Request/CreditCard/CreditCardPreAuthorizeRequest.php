<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

class CreditCardPreAuthorizeRequest extends AbstractCreditCardAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $pseudoPan,
        string $referenceNumber
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $context, $pseudoPan, $referenceNumber), [
            'request' => 'preauthorization',
        ]);
    }
}
