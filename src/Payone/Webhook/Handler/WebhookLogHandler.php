<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\DataHandler\WebhookLog\WebhookLogDataHandlerInterface;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class WebhookLogHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly TransactionDataHandlerInterface $transactionDataHandler,
        private readonly WebhookLogDataHandlerInterface $webhookLogDataHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        return isset(
            $data['txid'],
            $data['txaction'],
            $data['sequencenumber'],
            $data['clearingtype']
        );
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $data = $request->request->all();

        /** @var PaymentTransaction|null $paymentTransaction */
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            (int) $data['txid']
        );

        if ($paymentTransaction === null) {
            $this->logger->warning(sprintf('Could not get transaction for id %s', (int) $data['txid']));

            return;
        }

        // Sanitize incoming data
        $data = $this->utf8EncodeRecursive($data);

        $this->webhookLogDataHandler->createWebhookLog(
            $paymentTransaction->getOrder(),
            $data,
            $salesChannelContext->getContext()
        );
    }

    private function utf8EncodeRecursive(array $transactionData): array
    {
        foreach ($transactionData as &$transactionValue) {
            if (\is_array($transactionValue)) {
                $transactionValue = $this->utf8EncodeRecursive($transactionValue);

                continue;
            }

            $transactionValue = utf8_encode((string) $transactionValue);
        }
        unset($transactionValue);

        return $transactionData;
    }
}
