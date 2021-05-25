<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalPreAuthorizeRequestParameterBuilder extends PaypalAuthorizeRequestParameterBuilder
{
    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ): array {
        return array_merge(parent::getRequestParameter($paymentTransaction, $requestData, $salesChannelContext, $paymentMethod, $action), [
            'request' => 'preauthorization',
        ]);
    }

    public function supports(string $paymentMethod, string $action = ''): bool
    {
        return $paymentMethod === PayonePaypal::class && $action === 'preauthorization';
    }
}
