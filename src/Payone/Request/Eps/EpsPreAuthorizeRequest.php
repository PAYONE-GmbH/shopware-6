<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Eps;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class EpsPreAuthorizeRequest extends AbstractEpsAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context,
        string $referenceNumber
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $dataBag, $context, $referenceNumber), [
            'request' => 'preauthorization',
        ]);
    }
}
