<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    /**
     * payone_payment_status.action -> state_machine_state.technical_name
     *
     * @var array
     */
    private const STATE_MAPPING = [
        'appointed' => 'open',
        'paid'      => 'paid',
        'capture'   => 'paid',
        'completed'   => 'paid',
    ];

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        EntityRepositoryInterface $stateRepository,
        TransactionDataHandlerInterface $dataHandler
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateRepository            = $stateRepository;
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
            $data[CustomFieldInstaller::ALLOW_CAPTURE] = true;
        }

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $transactionData);

        $state = $this->getStateByTechnicalName($transactionData['txaction']);

        if (!$state) {
            return;
        }

        $update = [
            'id'      => $paymentTransaction->getOrderTransaction()->getId(),
            'stateId' => $state->getId(),
        ];

        $this->orderTransactionRepository->update([$update], $salesChannelContext->getContext());
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

    private function getStateByTechnicalName(string $action): ?StateMachineStateEntity
    {
        if (!array_key_exists($action, self::STATE_MAPPING)) {
            return null;
        }

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $filter   = new EqualsFilter('state_machine_state.technicalName', self::STATE_MAPPING[$action]);
        $criteria->addFilter($filter);

        return $this->stateRepository->search($criteria, $context)->first();
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
}
