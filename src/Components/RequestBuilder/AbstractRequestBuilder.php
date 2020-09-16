<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestBuilder;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractRequestBuilder
{
    /** @var LineItemHydratorInterface */
    protected $lineItemHydrator;

    public function __construct(LineItemHydratorInterface $lineItemHydrator)
    {
        $this->lineItemHydrator = $lineItemHydrator;
    }

    abstract public function supports(string $paymentMethodId): bool;

    abstract public function getAdditionalRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        ParameterBag $parameterBag
    ): array;
}
