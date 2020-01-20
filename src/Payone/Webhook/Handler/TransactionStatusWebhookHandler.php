<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    /** @var TransactionDataHandler */
    private $transactionDataHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransactionStatusServiceInterface $transactionStatusService,
        TransactionDataHandler $transactionDataHandler,
        LoggerInterface $logger
    ) {
        $this->transactionStatusService = $transactionStatusService;
        $this->transactionDataHandler   = $transactionDataHandler;
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
    public function process(SalesChannelContext $salesChannelContext, array $data): void
    {
        try {
            /** @var null|PaymentTransaction $paymentTransaction */
            $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
                $salesChannelContext->getContext(),
                (int) $data['txid']
            );

            if (!$paymentTransaction) {
                $this->logger->warning(sprintf('Could not get transaction for id %s', (int) $data['txid']));

                return;
            }

            $enhancedData = $this->transactionDataHandler->enhanceStatusWebhookData($paymentTransaction, $data);
            $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
            $this->transactionDataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $enhancedData);

            $this->transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction->getOrderTransaction(), $data);
        } catch (Throwable $exception) {
            $this->logger->warning($exception->getMessage(), $exception->getTrace());
        }
    }
}
