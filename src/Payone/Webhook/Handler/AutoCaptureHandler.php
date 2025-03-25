<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureServiceInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\PaymentMethod\PayonePrepayment;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class AutoCaptureHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly TransactionDataHandlerInterface $transactionDataHandler,
        private readonly AutomaticCaptureServiceInterface $automaticCaptureService
    ) {
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            $request->request->getInt('txid')
        );

        if (!$paymentTransaction instanceof PaymentTransaction) {
            return;
        }

        $txAction = $request->request->get('txaction');
        $paymentMethodId = $paymentTransaction->getOrderTransaction()->getPaymentMethodId();

        // For prepayment, capturing is only permitted for “paid” webhooks. For other payment methods, only the “appointed” webhook may be captured.
        if (($txAction === TransactionStatusService::ACTION_PAID && $paymentMethodId === PayonePrepayment::UUID)
            || ($txAction === TransactionStatusService::ACTION_APPOINTED && $paymentMethodId !== PayonePrepayment::UUID)) {
            $this->automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
        }
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        return isset($data['txid']) && \in_array(
            ($data['txaction'] ?? null),
            [
                TransactionStatusService::ACTION_APPOINTED,
                TransactionStatusService::ACTION_PAID,
            ],
            true
        );
    }
}
