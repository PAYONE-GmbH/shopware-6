<?php

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    public function __construct(TransactionStatusServiceInterface $transactionStatusService)
    {
        $this->transactionStatusService = $transactionStatusService;
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
    public function processAsync(SalesChannelContext $salesChannelContext, array $data): Response
    {
        try {
            $this->transactionStatusService->persistTransactionStatus($salesChannelContext, $data);
        } catch (Throwable $exception) {
            return new Response(self::RESPONSE_TSNOTOK);
        }

        return new Response(self::RESPONSE_TSOK);
    }
}
