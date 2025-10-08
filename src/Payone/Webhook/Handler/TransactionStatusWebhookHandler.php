<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

readonly class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private TransactionStatusServiceInterface $transactionStatusService,
        private TransactionDataHandler $transactionDataHandler,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (\array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $data = $request->request->all();

        /** @var PaymentTransaction|null $paymentTransaction */
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            (int) $data['txid'],
        );

        if (null === $paymentTransaction) {
            $this->logger->warning(sprintf('Could not get transaction for id %s', (int) $data['txid']));

            return;
        }

        // Sanitize incoming data
        $data = $this->utf8EncodeRecursive($data);

        $payoneTransactionData = $this->transactionDataHandler->getTransactionDataFromWebhook($paymentTransaction, $data);

        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $payoneTransactionData);
        $this->transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $data);
    }

    private function utf8EncodeRecursive(array $transactionData): array
    {
        foreach ($transactionData as &$transactionValue) {
            if (\is_array($transactionValue)) {
                $transactionValue = $this->utf8EncodeRecursive($transactionValue);

                continue;
            }

            $transactionValue = mb_convert_encoding((string) $transactionValue, 'UTF-8', 'ISO-8859-1');
        }
        unset($transactionValue);

        return $transactionData;
    }
}
