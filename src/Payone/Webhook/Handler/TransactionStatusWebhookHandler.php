<?php

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use Symfony\Component\HttpFoundation\Response;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    private const RESPONSE_OK      = 'TSOK';
    private const RESPONSE_FAILURE = 'TSNOTOK';

    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(TransactionStatusServiceInterface $transactionStatusService, ConfigReaderInterface $configReader)
    {
        $this->transactionStatusService = $transactionStatusService;
        $this->configReader             = $configReader;
    }

    public function supports(array $data): bool
    {
        if (array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function processAsync(array $data): Response
    {
        // TODO: SalesChannel-Id?
        $storedKey = $this->configReader->read('', 'portal_key')->first()->getValue();

        // TODO: move to helper class
        if (!array_key_exists('key', $data) || !$storedKey || $data['key'] !== hash('md5', $storedKey)) {
            return new Response(self::RESPONSE_FAILURE);
        }

        $statusStruct = new TransactionStatusStruct($data);

        register_shutdown_function(function (TransactionStatusStruct $statusStruct) {
            $this->transactionStatusService->persistTransactionStatus($statusStruct);
        }, $statusStruct);

        return new Response(self::RESPONSE_OK);
    }
}
