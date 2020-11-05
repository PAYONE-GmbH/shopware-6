<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Trustly;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

class TrustlyAuthorizeRequest extends AbstractTrustlyAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $referenceNumber,
        string $iban
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $context, $referenceNumber, $iban), [
            'request' => 'authorization',
        ]);
    }
}
