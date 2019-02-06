<?php

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Handler;

use PayonePayment\Bundle\PayoneBundle\Webhook\Struct\TransactionStatusStruct;
use Symfony\Component\HttpFoundation\Response;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    private const RESPONSE_OK = 'TSOK';
    private const RESPONSE_FAILURE = 'TSNOTOK';

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

        register_shutdown_function(function ($transactionStatusStruct) {
            $this->handlePaymentStatus($transactionStatusStruct);
        }, $statusStruct);

        return new Response(self::RESPONSE_OK);
    }

    private function handlePaymentStatus(TransactionStatusStruct $statusStruct)
    {
        //TODO: Implement actual status update for the transaction :-)

        echo '<pre>';
        print_r($statusStruct);
        echo '</pre>';
        exit();
    }
}
