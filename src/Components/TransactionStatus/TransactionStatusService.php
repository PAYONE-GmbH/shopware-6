<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    private const ACTION_APPOINTED = 'appointed';
    private const ACTION_PAID = 'paid';
    private const ACTION_CAPTURE = 'capture';
    private const ACTION_COMPLETED = 'completed';

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $stateHandler,
        TransactionDataHandlerInterface $dataHandler
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateHandler = $stateHandler;
        $this->dataHandler                = $dataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function persistTransactionStatus(SalesChannelContext $salesChannelContext, array $transactionData): void
    {
        $paymentTransaction = $this->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            (int) $transactionData['txid']
        );

        if (!$paymentTransaction) {
            throw new RuntimeException(sprintf(
                'Could not find an order transaction by payone transaction id "%s"',
                $transactionData['txid']
            ));
        }

        $transactionData = array_map('utf8_encode', $transactionData);

        $data[CustomFieldInstaller::SEQUENCE_NUMBER]   = (int) $transactionData['sequencenumber'];
        $data[CustomFieldInstaller::TRANSACTION_STATE] = $transactionData['txaction'];

        $customFields = $paymentTransaction->getCustomFields();

        if ($this->shouldAllowCapture($transactionData, $customFields)) {
            $data[CustomFieldInstaller::ALLOW_CAPTURE] = true;
        }

        if ($this->shouldAllowRefund($transactionData, $customFields)) {
            $data[CustomFieldInstaller::ALLOW_REFUND] = true;
        }

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $transactionData);

        if ($this->isTransactionOpen($transactionData)) {
            $this->stateHandler->open(
                $paymentTransaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );
        }

        if ($this->isTransactioNPaid($transactionData)) {
            $this->stateHandler->pay(
                $paymentTransaction->getOrderTransaction()->getId(),
                $salesChannelContext->getContext()
            );
        }
    }

    private function getPaymentTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?PaymentTransaction
    {
        $field = 'order_transaction.customFields.' . CustomFieldInstaller::TRANSACTION_ID;

        $criteria = new Criteria();
        $filter   = new EqualsFilter($field, $payoneTransactionId);
        $criteria->addFilter($filter);

        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (null === $transaction) {
            return null;
        }

        return PaymentTransaction::fromOrderTransaction($transaction);
    }

    private function shouldAllowCapture(array $transactionData, array $customFields): bool
    {
        if ($customFields[CustomFieldInstaller::LAST_REQUEST] !== 'preauthorization') {
            return false;
        }

        return $transactionData['txaction'] === 'appointed';
    }

    private function shouldAllowRefund(array $transactionData, array $customFields): bool
    {
        if ($customFields[CustomFieldInstaller::LAST_REQUEST] !== 'authorization') {
            return false;
        }

        return $transactionData['txaction'] === 'appointed';
    }

    private function isTransactionOpen(array $transactionData): bool
    {
        return $transactionData['txaction'] === self::ACTION_APPOINTED;
    }

    private function isTransactioNPaid(array $transactionData): bool
    {
        if ($transactionData['txaction'] === self::ACTION_PAID) {
            return true;
        }

        if ($transactionData['txaction'] === self::ACTION_CAPTURE) {
            return true;
        }

        if ($transactionData['txaction'] === self::ACTION_COMPLETED) {
            return true;
        }

        return false;
    }
}
