<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionDataHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class TransactionDataHandler implements TransactionDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    public function __construct(EntityRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function saveTransactionData(
        SalesChannelContext $context,
        PaymentTransactionStruct $transaction,
        array $data
    ) {
        if (null === $transaction->getOrderTransaction()) {
            return;
        }

        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];
        $customFields = array_merge($customFields, $data);

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $context->getContext());
    }

    public function logResponse(
        SalesChannelContext $context,
        PaymentTransactionStruct $transaction,
        array $response
    ) {
        $key = (new DateTime())->format(DATE_ATOM);

        if (null === $transaction->getOrderTransaction()) {
            return;
        }

        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;

        $update = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $context->getContext());
    }
}
