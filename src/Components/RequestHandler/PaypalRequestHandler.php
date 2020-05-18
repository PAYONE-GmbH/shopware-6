<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestHandler;

use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaypalRequestHandler extends AbstractRequestHandler
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePaypal::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array
    {
        return [];
    }
}
