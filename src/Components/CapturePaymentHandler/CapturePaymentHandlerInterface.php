<?php

declare(strict_types=1);

namespace PayonePayment\Components\CapturePaymentHandler;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

interface CapturePaymentHandlerInterface
{

//    public function fullCapture(string $transactionId, Context $context): JsonResponse;

//    public function partialCapture(ParameterBag $parameterBag, Context $context): JsonResponse;

//    /**
//     * @throws InvalidOrderException
//     * @throws PayoneRequestException
//     */
//    public function captureTransaction(OrderTransactionEntity $orderTransaction, Context $context): void;
}
