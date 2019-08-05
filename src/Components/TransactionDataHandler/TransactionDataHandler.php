<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class TransactionDataHandler implements TransactionDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    public function __construct(EntityRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
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
        $transaction->setCustomFields($customFields);

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

        $this->transactionRepository->update([$update], $context);
    }
}
