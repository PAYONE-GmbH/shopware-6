<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class TransactionStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    /** @var TransactionDataHandlerInterface */
    private $transactionDataHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransactionStatusServiceInterface $transactionStatusService,
        TransactionDataHandlerInterface $transactionDataHandler,
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
        /** @var null|PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            (int) $data['txid']
        );

        if (null === $paymentTransaction) {
            $this->logger->warning(sprintf('Could not get transaction for id %s', (int) $data['txid']));

            return;
        }

        $data = $this->transactionDataHandler->enhanceStatusWebhookData($paymentTransaction, $data);

        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->transactionDataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['transaction' => $data]);
        $this->transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $data);
    }
}
