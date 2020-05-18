<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestHandler;

use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayolutionInvoicingRequestHandler extends AbstractRequestHandler
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePayolutionInvoicing::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array
    {
        $currency   = $transaction->getOrder()->getCurrency();
        $orderLines = [];

        if ($parameterBag) {
            $orderLines = $parameterBag->get('orderLines', []);
        }

        return $this->mapPayoneOrderLines($currency, $transaction->getOrder()->getLineItems(), $orderLines);
    }
}
