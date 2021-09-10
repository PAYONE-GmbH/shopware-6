<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\Transaction;

use DateTime;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class TransactionDataHandler implements TransactionDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    public function __construct(EntityRepositoryInterface $transactionRepository, CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->transactionRepository = $transactionRepository;
        $this->currencyPrecision     = $currencyPrecision;
    }

    public function getPaymentTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?PaymentTransaction
    {
        $field = 'order_transaction.customFields.' . CustomFieldInstaller::TRANSACTION_ID;

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter($field, $payoneTransactionId))
            ->addAssociation('paymentMethod')
            ->addAssociation('order')
            ->addAssociation('order.currency');

        /** @var null|OrderTransactionEntity $transaction */
        $transaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $transaction || null === $transaction->getOrder()) {
            return null;
        }

        return PaymentTransaction::fromOrderTransaction($transaction, $transaction->getOrder());
    }

    public function getCustomFieldsFromWebhook(PaymentTransaction $paymentTransaction, array $transactionData): array
    {
        $newCustomFields      = [];
        $existingCustomFields = $paymentTransaction->getCustomFields() ?? [];

        $currentSequenceNumber                                  = array_key_exists(CustomFieldInstaller::SEQUENCE_NUMBER, $existingCustomFields) ? $existingCustomFields[CustomFieldInstaller::SEQUENCE_NUMBER] : 0;
        $newCustomFields[CustomFieldInstaller::SEQUENCE_NUMBER] = max((int) $transactionData['sequencenumber'], $currentSequenceNumber);

        $newCustomFields[CustomFieldInstaller::TRANSACTION_STATE] = strtolower($transactionData['txaction']);

        if ($this->canChangeCapturableState($transactionData)) {
            $newCustomFields[CustomFieldInstaller::ALLOW_CAPTURE] = $this->shouldAllowCapture($paymentTransaction, $transactionData);
        }

        if ($this->canChangeRefundableState($transactionData)) {
            $newCustomFields[CustomFieldInstaller::ALLOW_REFUND] = $this->shouldAllowRefund($paymentTransaction, $transactionData);
        }

        if (in_array($newCustomFields[CustomFieldInstaller::TRANSACTION_STATE], [TransactionStatusService::ACTION_PAID, TransactionStatusService::ACTION_COMPLETED])) {
            $newCustomFields[CustomFieldInstaller::CAPTURED_AMOUNT] = $this->getCapturedAmount($paymentTransaction, $transactionData);
        }

        return $newCustomFields;
    }

    public function saveTransactionData(PaymentTransaction $transaction, Context $context, array $data): void
    {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];
        $customFields = array_merge($customFields, $data);

        $this->updateTransactionCustomFields($transaction, $context, $customFields);
    }

    public function logResponse(PaymentTransaction $transaction, Context $context, array $data): void
    {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $data;

        $this->updateTransactionCustomFields($transaction, $context, $customFields);
    }

    public function incrementSequenceNumber(PaymentTransaction $transaction, Context $context): void
    {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];

        $this->updateTransactionCustomFields($transaction, $context, $customFields);
    }

    public function saveTransactionState(string $stateId, PaymentTransaction $transaction, Context $context): void
    {
        $update = [
            'id'      => $transaction->getOrderTransaction()->getId(),
            'stateId' => $stateId,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([$update], $context);
        });
    }

    private function updateTransactionCustomFields(PaymentTransaction $transaction, Context $context, array $customFields): void
    {
        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $transaction->getOrderTransaction()->setCustomFields($customFields);
        $transaction->setCustomFields($customFields);

        $this->transactionRepository->update([$update], $context);
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
        $txAction = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;

        // The following TX actions do not affect any capturable or refundable state
        return in_array($txAction, [
            TransactionStatusService::ACTION_TRANSFER,
            TransactionStatusService::ACTION_REMINDER,
            TransactionStatusService::ACTION_INVOICE,
            TransactionStatusService::ACTION_VAUTHORIZATION,
            TransactionStatusService::ACTION_VSETTLEMENT,
        ]);
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

    private function shouldAllowCapture(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass) {
            return false;
        }

        return $handlerClass::isCapturable($transactionData, $paymentTransaction->getCustomFields());
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

        if (!$handlerClass) {
            return false;
        }

        return $handlerClass::isRefundable($transactionData, $paymentTransaction->getCustomFields());
    }

    private function getCapturedAmount(PaymentTransaction $paymentTransaction, array $transactionData): int
    {
        $currency              = $paymentTransaction->getOrder()->getCurrency();
        $customFields          = $paymentTransaction->getOrderTransaction()->getCustomFields() ?? [];
        $currentCapturedAmount = 0;
        $receivable            = 0;

        if (!$currency) {
            return 0;
        }

        if (array_key_exists(CustomFieldInstaller::CAPTURED_AMOUNT, $customFields) &&
            $customFields[CustomFieldInstaller::CAPTURED_AMOUNT] !== 0) {
            $currentCapturedAmount = $customFields[CustomFieldInstaller::CAPTURED_AMOUNT];
        }

        if (array_key_exists('receivable', $transactionData)) {
            $receivable = $this->currencyPrecision->getRoundedTotalAmount((float) $transactionData['receivable'], $currency);
        }

        return max($currentCapturedAmount, $receivable);
    }

    private function getHandlerIdentifier(PaymentTransaction $paymentTransaction): string
    {
        $paymentMethodEntity = $paymentTransaction->getOrderTransaction()->getPaymentMethod();

        if (!$paymentMethodEntity) {
            return '';
        }

        $handlerClass = $paymentMethodEntity->getHandlerIdentifier();

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('The handler class %s for payment method %s does not exist.', $paymentMethodEntity->getName(), $handlerClass));
        }

        return $handlerClass;
    }
}
