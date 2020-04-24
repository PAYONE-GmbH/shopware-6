<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInstallment;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInstallmentPreAuthorizeRequest extends AbstractPayolutionInstallmentAuthorizeRequest
{
    public function getRequestParameters(PaymentTransaction $transaction, RequestDataBag $dataBag, SalesChannelContext $context): array
    {
        return array_merge(parent::getRequestParameters($transaction, $dataBag, $context), [
            'request' => 'preauthorization',
        ]);
    }
}
