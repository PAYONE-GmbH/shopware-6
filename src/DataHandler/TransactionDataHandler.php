<?php

declare(strict_types=1);

namespace PayonePayment\DataHandler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Service\CurrencyPrecisionService;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

readonly class TransactionDataHandler
{
    public function __construct(
        private EntityRepository $transactionRepository,
        private CurrencyPrecisionService $currencyPrecision,
    ) {
    }

    public function getPaymentTransactionByPayoneTransactionId(
        Context $context,
        int $payoneTransactionId,
    ): PaymentTransaction|null {
        $criteria = (new Criteria())
            ->addAssociation(PayonePaymentOrderTransactionExtension::NAME)
            ->addFilter(
                new EqualsFilter(PayonePaymentOrderTransactionExtension::NAME . '.transactionId', $payoneTransactionId),
            )
            ->addAssociation('paymentMethod')
            ->addAssociation('order')
            ->addAssociation('order.lineItems')
            ->addAssociation('order.currency')
        ;

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $transaction || null === $transaction->getOrder()) {
            return null;
        }

        return PaymentTransaction::fromOrderTransaction($transaction, $transaction->getOrder());
    }

    public function getTransactionDataFromWebhook(
        PaymentTransaction $paymentTransaction,
        array $transactionData,
    ): array {
        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $paymentTransaction->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        $key = (new \DateTime())->format(\DATE_ATOM);

        $newTransactionData = [
            'id' => $payoneTransactionData->getId(),
        ];

        $currentSequenceNumber = $payoneTransactionData->getSequenceNumber();

        $newTransactionData['sequenceNumber'] = \max(
            (int) $transactionData['sequencenumber'],
            $currentSequenceNumber,
        );

        $newTransactionData['transactionState']  = \strtolower((string) $transactionData['txaction']);
        $newTransactionData['authorizationType'] = $payoneTransactionData->getAuthorizationType();

        $newTransactionData['transactionData'] = \array_merge(
            $payoneTransactionData->getTransactionData() ?? [],
            [ $key => $transactionData ],
        );

        if ($this->canChangeCapturableState($transactionData)) {
            $newTransactionData['allowCapture'] = $this->shouldAllowCapture(
                $paymentTransaction,
                $transactionData,
                $newTransactionData,
            );
        }

        if ($this->canChangeRefundableState($transactionData)) {
            $newTransactionData['allowRefund'] = $this->shouldAllowRefund(
                $paymentTransaction,
                $transactionData,
            );
        }

        $transactionDone = \in_array(
            $newTransactionData['transactionState'],
            [  TransactionActionEnum::PAID->value, TransactionActionEnum::COMPLETED->value ],
            true,
        );

        if ($transactionDone) {
            $newTransactionData['capturedAmount'] = $this->getCapturedAmount($paymentTransaction, $transactionData);
        }

        return $newTransactionData;
    }

    public function saveTransactionData(PaymentTransaction $transaction, Context $context, array $data): void
    {
        $extension = $transaction->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if ($extension instanceof PayonePaymentOrderTransactionDataEntity) {
            $data['id'] = $extension->getId();
        }

        $this->transactionRepository->upsert([[
            'id'                                         => $transaction->getOrderTransaction()->getId(),
            PayonePaymentOrderTransactionExtension::NAME => $data,
        ]], $context);
    }

    public function logResponse(PaymentTransaction $transaction, Context $context, array $data): void
    {
        $key = (new \DateTime())->format(\DATE_ATOM);

        $newTransactionData = [
            'transactionData' => [
                $key => $data,
            ],
        ];

        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        $payoneTransactionData = $transaction->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        if (null !== $payoneTransactionData) {
            $newTransactionData = [
                'id' => $payoneTransactionData->getId(),
            ];

            $newTransactionData['transactionData'] = \array_merge(
                $payoneTransactionData->getTransactionData() ?? [],
                [$key => $data],
            );
        }

        $this->transactionRepository->update([[
            'id'                                         => $transaction->getOrderTransaction()->getId(),
            PayonePaymentOrderTransactionExtension::NAME => $newTransactionData,
        ]], $context);
    }

    public function incrementSequenceNumber(PaymentTransaction $transaction, array &$transactionData): void
    {
        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        $payoneTransactionData = $transaction->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        if (null === $payoneTransactionData) {
            return;
        }

        $transactionData['id']             = $payoneTransactionData->getId();
        $transactionData['sequenceNumber'] = $payoneTransactionData->getSequenceNumber() + 1;
    }

    public function saveTransactionState(string $stateId, PaymentTransaction $transaction, Context $context): void
    {
        $update = [
            'id'      => $transaction->getOrderTransaction()->getId(),
            'stateId' => $stateId,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([ $update ], $context);
        });
    }

    /**
     * Checks if the TX status notification never changes the capturable
     * or the refundable state of a transaction.
     *
     * @param array $transactionData Data of the TX status notification
     *
     * @return bool True if the TX status notification never changes the capturable or refundable state of a transaction
     */
    private function neverChangesCapturableOrRefundableState(array $transactionData): bool
    {
        $txAction = isset($transactionData['txaction']) ? \strtolower((string) $transactionData['txaction']) : null;

        // The following TX actions do not affect any capturable or refundable state
        return \in_array($txAction, [
            TransactionActionEnum::TRANSFER->value,
            TransactionActionEnum::REMINDER->value,
            TransactionActionEnum::INVOICE->value,
            TransactionActionEnum::VAUTHORIZATION->value,
            TransactionActionEnum::VSETTLEMENT->value,
        ], true);
    }

    /**
     * Checks if the TX status notification can change the capturable state.
     *
     * @param array $transactionData Data of the TX status notification
     *
     * @return bool True if the TX status notification can change the capturable state
     */
    private function canChangeCapturableState(array $transactionData): bool
    {
        if ($this->neverChangesCapturableOrRefundableState($transactionData)) {
            return false;
        }

        return true;
    }

    private function shouldAllowCapture(
        PaymentTransaction $paymentTransaction,
        array $transactionData,
        array $payoneTransactionData,
    ): bool {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass || !class_exists($handlerClass)) {
            return false;
        }

        return $handlerClass::isCapturable($transactionData, $payoneTransactionData);
    }

    /**
     * Checks if the TX status notification can change the refundable state.
     *
     * @param array $transactionData Data of the TX status notification
     *
     * @return bool True if the TX status notification can change the refundable state
     */
    private function canChangeRefundableState(array $transactionData): bool
    {
        if ($this->neverChangesCapturableOrRefundableState($transactionData)) {
            return false;
        }

        return true;
    }

    private function shouldAllowRefund(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass || !\class_exists($handlerClass)) {
            return false;
        }

        return $handlerClass::isRefundable($transactionData);
    }

    private function getCapturedAmount(PaymentTransaction $paymentTransaction, array $transactionData): int
    {
        $currency = $paymentTransaction->getOrder()->getCurrency();

        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        $payoneTransactionData = $paymentTransaction->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        $currentCapturedAmount = 0;
        $receivable            = 0;

        if (!$currency || null === $payoneTransactionData) {
            return 0;
        }

        if (null !== $payoneTransactionData->getCapturedAmount()) {
            $currentCapturedAmount = $payoneTransactionData->getCapturedAmount();
        }

        if (\array_key_exists('receivable', $transactionData)) {
            $receivable = $this->currencyPrecision->getRoundedTotalAmount(
                (float) $transactionData['receivable'],
                $currency,
            );
        }

        return \max($currentCapturedAmount, $receivable);
    }

    private function getHandlerIdentifier(PaymentTransaction $paymentTransaction): string
    {
        $paymentMethodEntity = $paymentTransaction->getOrderTransaction()->getPaymentMethod();

        if (!$paymentMethodEntity) {
            return '';
        }

        $handlerClass = $paymentMethodEntity->getHandlerIdentifier();

        if (!\class_exists($handlerClass)) {
            throw new \RuntimeException(\sprintf(
                'The handler class %s for payment method %s does not exist.',
                $paymentMethodEntity->getName(),
                $handlerClass,
            ));
        }

        return $handlerClass;
    }
}
