<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Capture;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

interface CaptureTransactionHandlerInterface
{
    public function capture(ParameterBag $parameterBag, Context $context): JsonResponse;
}
