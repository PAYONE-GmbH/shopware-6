<?php

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use Symfony\Component\HttpFoundation\Response;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    private const RESPONSE_OK = 'TSOK';
    private const RESPONSE_FAILURE = 'TSNOTOK';

    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    public function __construct(TransactionStatusServiceInterface $transactionStatusService)
    {
        $this->transactionStatusService = $transactionStatusService;
    }

    /**
     * {@inheritdoc}
     */
    public function processAsync(array $data): Response
    {
        //TODO: Verify key - maybe in the processor instead of in the final handler?
        if (!array_key_exists('key', $data)) {
            return new Response(self::RESPONSE_FAILURE);
        }

        $statusStruct = new TransactionStatusStruct($data);
        $this->transactionStatusService->persistTransactionStatus($statusStruct);

        exit;
        register_shutdown_function(function ($transactionStatusStruct) {
            $this->handlePaymentStatus($transactionStatusStruct);
        }, $statusStruct);

        return new Response(self::RESPONSE_OK);
    }

    private function handlePaymentStatus(TransactionStatusStruct $statusStruct): void
    {
        $this->transactionStatusService->persistTransactionStatus($statusStruct);
    }
}
