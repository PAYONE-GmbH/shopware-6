<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class TransactionDataHandler implements TransactionDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    public function __construct(EntityRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function getPaymentTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?PaymentTransaction
    {
        $field = 'order_transaction.customFields.' . CustomFieldInstaller::TRANSACTION_ID;

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter($field, $payoneTransactionId))
            ->addAssociation('paymentMethod')
            ->addAssociation('order')
            ->addAssociation('order.currency');

        $transaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $transaction) {
            return null;
        }

        return PaymentTransaction::fromOrderTransaction($transaction);
    }

    public function enhanceStatusWebhookData(PaymentTransaction $paymentTransaction, array $transactionData): array
    {
        $data = array_map('utf8_encode', $transactionData);

        $data[CustomFieldInstaller::SEQUENCE_NUMBER]   = (int) $transactionData['sequencenumber'];
        $data[CustomFieldInstaller::TRANSACTION_STATE] = strtolower($transactionData['txaction']);
        $data[CustomFieldInstaller::ALLOW_CAPTURE]     = $this->shouldAllowCapture($paymentTransaction, $transactionData);
        $data[CustomFieldInstaller::ALLOW_REFUND]      = $this->shouldAllowRefund($paymentTransaction, $transactionData);

        return $data;
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

    private function shouldAllowCapture(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass) {
            return false;
        }

        return $handlerClass::isCapturable($transactionData, $paymentTransaction->getCustomFields());
    }

    private function shouldAllowRefund(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $handlerClass = $this->getHandlerIdentifier($paymentTransaction);

        if (!$handlerClass) {
            return false;
        }

        return $handlerClass::isRefundable($transactionData, $paymentTransaction->getCustomFields());
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
