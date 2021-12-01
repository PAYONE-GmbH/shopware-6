<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\Transaction;

use DateTime;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
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
        $criteria = (new Criteria())
            ->addAssociation(PayonePaymentOrderTransactionExtension::NAME)
            ->addFilter(new EqualsFilter(PayonePaymentOrderTransactionExtension::NAME . '.transactionId', $payoneTransactionId))
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

    //TODO: rename here and in interface
    public function getCustomFieldsFromWebhook(PaymentTransaction $paymentTransaction, array $transactionData): array
    {
        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $paymentTransaction->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        $newTransactionData = [
            'id' => $payoneTransactionData->getId()
        ];

        $currentSequenceNumber                                  = $payoneTransactionData->getSequenceNumber();

        $newTransactionData['sequenceNumber'] = max((int) $transactionData['sequencenumber'], $currentSequenceNumber);
        $newTransactionData['transactionState'] = strtolower($transactionData['txaction']);
        $newTransactionData['authorizationType'] = $payoneTransactionData->getAuthorizationType();

        //TODO: fix
        if ($this->canChangeCapturableState($transactionData)) {
            $newTransactionData['allowCapture'] = $this->shouldAllowCapture($paymentTransaction, $transactionData, $newTransactionData);
        }

        if ($this->canChangeRefundableState($transactionData)) {
            $newTransactionData['allowRefund'] = $this->shouldAllowRefund($paymentTransaction, $transactionData);
        }

        if (in_array($newTransactionData['transactionState'], [TransactionStatusService::ACTION_PAID, TransactionStatusService::ACTION_COMPLETED])) {
            $newTransactionData['capturedAmount'] = $this->getCapturedAmount($paymentTransaction, $transactionData);
        }

        return $newTransactionData;
    }

    public function saveTransactionData(PaymentTransaction $transaction, Context $context, array $data): void
    {
        $this->transactionRepository->upsert([[
           'id'                                         => $transaction->getOrderTransaction()->getId(),
           PayonePaymentOrderTransactionExtension::NAME => $data,
        ]], $context);
    }

    public function logResponse(PaymentTransaction $transaction, Context $context, array $data): void
    {
        //TODO: get existing data -> used in webhook handler

        $key = (new DateTime())->format(DATE_ATOM);

        $this->transactionRepository->update([[
            'id'                                         => $transaction->getOrderTransaction()->getId(),
            PayonePaymentOrderTransactionExtension::NAME => [
                'transactionData' => [
                    $key => $data,
                ],
            ],
        ]], $context);
    }

    public function incrementSequenceNumber(PaymentTransaction $transaction, Context $context): void
    {
        //TODO: update
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];
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

    private function shouldAllowCapture(PaymentTransaction $paymentTransaction, array $transactionData, array $payoneTransactionData): bool
    {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass) {
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
