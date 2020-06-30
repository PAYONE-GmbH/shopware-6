<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestBuilder;

use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayolutionInstallmentRequestBuilder extends AbstractRequestBuilder
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePayolutionInstallment::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag): array
    {
        $currency   = $transaction->getOrder()->getCurrency();
        $orderLines = $parameterBag->get('orderLines', []);

        if (empty($orderLines) || empty($currency) || empty($transaction->getOrder()->getLineItems())) {
            return [];
        }

        return $this->mapPayoneOrderLines($currency, $transaction->getOrder()->getLineItems(), $orderLines);
    }
}
