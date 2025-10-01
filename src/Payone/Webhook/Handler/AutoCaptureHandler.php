<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Provider\Payone\PaymentMethod\PrepaymentPaymentMethod;
use PayonePayment\Service\AutomaticCaptureService;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

readonly class AutoCaptureHandler implements WebhookHandlerInterface
{
    public function __construct(
        private TransactionDataHandler $transactionDataHandler,
        private AutomaticCaptureService $automaticCaptureService,
    ) {
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            $request->request->getInt('txid'),
        );

        if (!$paymentTransaction instanceof PaymentTransaction) {
            return;
        }

        $txAction        = $request->request->get('txaction');
        $paymentMethodId = $paymentTransaction->getOrderTransaction()->getPaymentMethodId();

        // For prepayment, capturing is only permitted for “paid” webhooks. For other payment methods, only the “appointed” webhook may be captured.
        if (
            (TransactionActionEnum::PAID->value === $txAction && PrepaymentPaymentMethod::UUID === $paymentMethodId)
            || (TransactionActionEnum::APPOINTED->value === $txAction && PrepaymentPaymentMethod::UUID !== $paymentMethodId)
        ) {
            $this->automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
        }
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        return isset($data['txid']) && \in_array(
            ($data['txaction'] ?? null),
            [
                 TransactionActionEnum::APPOINTED->value,
                 TransactionActionEnum::PAID->value,
            ],
            true,
        );
    }
}
