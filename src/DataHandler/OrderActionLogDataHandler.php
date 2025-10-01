<?php

declare(strict_types=1);

namespace PayonePayment\DataHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

readonly class OrderActionLogDataHandler
{
    public function __construct(
        protected EntityRepository $orderActionLogRepository,
        protected LoggerInterface $logger,
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
        Context $context,
    ): void {
        $orderActionLog = [
            'orderId'         => $order->getId(),
            'transactionId'   => $response['txid'],
            'referenceNumber' => $order->getOrderNumber(),
            'request'         => $request['request'],
            'response'        => $response['status'],
            'amount'          => $request['amount'],
            'mode'            => $request['mode'],
            'merchantId'      => $request['mid'],
            'portalId'        => $request['portalid'],
            'requestDetails'  => $request,
            'responseDetails' => $response,
            'requestDateTime' => new \DateTime(),
        ];

        try {
            $this->orderActionLogRepository->create([$orderActionLog], $context);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to create order action log', [
                'message' => $exception->getMessage(),
                'data'    => $orderActionLog,
                'trace'   => $exception->getTraceAsString(),
            ]);
        }
    }
}
