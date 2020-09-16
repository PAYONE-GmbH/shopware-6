<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestBuilder;

use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class SecureInvoiceRequestBuilder extends AbstractRequestBuilder
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayoneSecureInvoice::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag): array
    {
        $currency   = $transaction->getOrder()->getCurrency();
        $orderLines = $parameterBag->get('orderLines', []);

        if ($currency === null || empty($orderLines) || empty($transaction->getOrder()->getLineItems())) {
            return [];
        }

        return $this->lineItemHydrator->mapPayoneOrderLinesByRequest($currency, $transaction->getOrder()->getLineItems(), $orderLines);
    }
}
