<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInvoicing;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInvoicingPreAuthorizeRequest extends AbstractPayolutionInvoicingAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        string $referenceNumber
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $dataBag, $context, $referenceNumber), [
            'request' => 'preauthorization',
        ]);
    }
}
