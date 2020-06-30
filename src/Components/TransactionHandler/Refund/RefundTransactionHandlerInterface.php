<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Refund;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

interface RefundTransactionHandlerInterface
{
    public function refund(ParameterBag $parameterBag, Context $context): JsonResponse;
}
