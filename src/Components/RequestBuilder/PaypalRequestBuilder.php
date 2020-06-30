<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestBuilder;

use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaypalRequestBuilder extends AbstractRequestBuilder
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePaypal::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag): array
    {
        return [];
    }
}
