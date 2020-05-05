<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentHandler\Refund;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

interface RefundPaymentHandlerInterface
{
    public function fullRefund(ParameterBag $parameterBag, Context $context): JsonResponse;

    public function partialRefund(ParameterBag $parameterBag, Context $context): JsonResponse;
}
