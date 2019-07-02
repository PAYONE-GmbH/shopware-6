<?php

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransactionStatusServiceInterface $transactionStatusService,
        LoggerInterface $logger
    ) {
        $this->transactionStatusService = $transactionStatusService;
        $this->logger                   = $logger;
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SalesChannelContext $salesChannelContext, array $data): Response
    {
        try {
            $this->transactionStatusService->persistTransactionStatus($salesChannelContext, $data);
        } catch (Throwable $exception) {
            $this->logger->warning($exception->getMessage());

            return new Response(self::RESPONSE_TSNOTOK);
        }

        return new Response(self::RESPONSE_TSOK);
    }
}
