<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayonePaymentHandlerInterface;
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
            ->addAssociation('paymentMethod');

        $transaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $transaction) {
            return null;
        }

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($transaction);

        if (!$paymentTransaction) {
            throw new RuntimeException(sprintf(
                'Could not find an order transaction by payone transaction id "%s"',
                $payoneTransactionId
            ));
        }

        return $paymentTransaction;
    }

    public function enhanceStatusWebhookData(PaymentTransaction $paymentTransaction, array $transactionData): array
    {
        $transactionData = array_map('utf8_encode', $transactionData);

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

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $transaction->getOrderTransaction()->setCustomFields($customFields);
        $transaction->setCustomFields(array_filter($customFields));

        $this->transactionRepository->update([$update], $context);
    }

    public function logResponse(PaymentTransaction $transaction, Context $context, array $response): void
    {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $transaction->getOrderTransaction()->setCustomFields($customFields);
        $transaction->setCustomFields($customFields);

        $this->transactionRepository->update([$update], $context);
    }

    public function incrementSequenceNumber(PaymentTransaction $transaction, Context $context): void
    {
        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $transaction->getOrderTransaction()->setCustomFields($customFields);
        $transaction->setCustomFields($customFields);

        $this->transactionRepository->update([$update], $context);
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

    private function shouldAllowCapture(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $paymentMethodEntity = $paymentTransaction->getOrderTransaction()->getPaymentMethod();

        if (!$paymentMethodEntity) {
            return false;
        }

        /** @var PayonePaymentHandlerInterface|string $handlerClass */
        $handlerClass = $paymentMethodEntity->getHandlerIdentifier();

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('The handler class %s for payment method %s does not exist.', $paymentMethodEntity->getName(), $handlerClass));
        }

        return $handlerClass::isCapturable($transactionData, $paymentTransaction->getCustomFields());
    }

    private function shouldAllowRefund(PaymentTransaction $paymentTransaction, array $transactionData): bool
    {
        $paymentMethodEntity = $paymentTransaction->getOrderTransaction()->getPaymentMethod();

        if (!$paymentMethodEntity) {
            return false;
        }

        /** @var string&PayonePaymentHandlerInterface $handlerClass */
        $handlerClass = $paymentMethodEntity->getHandlerIdentifier();

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('The handler class %s for payment method %s does not exist.', $paymentMethodEntity->getName(), $handlerClass));
        }

        return $handlerClass::isRefundable($transactionData, $paymentTransaction->getCustomFields());
    }
}
