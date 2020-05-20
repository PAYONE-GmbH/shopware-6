<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestBuilder;

use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayolutionInvoicingRequestBuilder extends AbstractRequestBuilder
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePayolutionInvoicing::UUID;
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
