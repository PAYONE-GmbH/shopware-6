<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\OrderActionLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class OrderActionLogDataHandler implements OrderActionLogDataHandlerInterface
{
    public function __construct(
        protected readonly EntityRepository $orderActionLogRepository
    ) {
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $response
     */
    public function createOrderActionLog(
        OrderEntity $order,
        array $request,
        array $response,
        Context $context
    ): void {
        $orderActionLog = [
            'orderId' => $order->getId(),
            'transactionId' => $response['txid'],
            'referenceNumber' => $order->getOrderNumber(),
            'request' => $request['request'],
            'response' => $response['status'],
            'amount' => $request['amount'],
            'mode' => $request['mode'],
            'merchantId' => $request['mid'],
            'portalId' => $request['portalid'],
            'requestDetails' => $request,
            'responseDetails' => $response,
            'requestDateTime' => new \DateTime(),
        ];

        $this->orderActionLogRepository->create([$orderActionLog], $context);
    }
}
