<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayonePaymentHandlerInterface;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class TransactionStatusService implements TransactionStatusServiceInterface
{
    public const ACTION_APPOINTED        = 'appointed';
    public const ACTION_PAID             = 'paid';
    public const ACTION_CAPTURE          = 'capture';
    public const ACTION_COMPLETED        = 'completed';
    public const ACTION_DEBIT            = 'debit';
    public const ACTION_AUTHORIZATION    = 'authorization';
    public const ACTION_PREAUTHORIZATION = 'preauthorization';
    public const ACTION_CANCELATION      = 'cancelation';
    public const ACTION_FAILED           = 'failed';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';

    public const AUTHORIZATION_TYPE_PREAUTHORIZATION = 'preauthorization';
    public const AUTHORIZATION_TYPE_AUTHORIZATION    = 'authorization';

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $stateHandler,
        TransactionDataHandlerInterface $dataHandler,
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $stateRepository
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateHandler               = $stateHandler;
        $this->dataHandler                = $dataHandler;
        $this->configReader               = $configReader;
        $this->stateRepository            = $stateRepository;
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
        $data[CustomFieldInstaller::TRANSACTION_STATE] = strtolower($transactionData['txaction']);
        $data[CustomFieldInstaller::ALLOW_CAPTURE]     = $this->shouldAllowCapture($transactionData, $paymentTransaction);
        $data[CustomFieldInstaller::ALLOW_REFUND]      = $this->shouldAllowRefund($transactionData, $paymentTransaction);

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $transactionData);

        $configuration    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $configurationKey = 'paymentStatus' . ucfirst(strtolower($transactionData['txaction']));

        if (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE && (float) $transactionData['receivable'] === 0.0) {
            // This is a special case of a capture of 0, which means a cancellation
            $configurationKey = 'paymentStatusCancelation';
        }

        if (!empty($configuration->get($configurationKey))) {
            if (!$this->stateExists($configuration->get($configurationKey), $salesChannelContext->getContext())) {
                throw new RuntimeException(sprintf('The mapped transaction state for %s does not exists. The mapping is therefore invalid.', $transactionData['txaction']));
            }

            $this->dataHandler->saveTransactionState(
                $configuration->get($configurationKey),
                $paymentTransaction,
                $salesChannelContext->getContext()
            );
        } else {
            if ($this->isTransactionOpen($transactionData)) {
                $this->stateHandler->reopen(
                    $paymentTransaction->getOrderTransaction()->getId(),
                    $salesChannelContext->getContext()
                );
            } elseif ($this->isTransactionPaid($transactionData)) {
                $this->stateHandler->pay(
                    $paymentTransaction->getOrderTransaction()->getId(),
                    $salesChannelContext->getContext()
                );
            } elseif ($this->isTransactionCancelled($transactionData)) {
                $this->stateHandler->cancel(
                    $paymentTransaction->getOrderTransaction()->getId(),
                    $salesChannelContext->getContext()
                );
            }
        }
    }

    private function getPaymentTransactionByPayoneTransactionId(Context $context, int $payoneTransactionId): ?PaymentTransaction
    {
        $field = 'order_transaction.customFields.' . CustomFieldInstaller::TRANSACTION_ID;

        $criteria = new Criteria();
        $filter   = new EqualsFilter($field, $payoneTransactionId);
        $criteria->addFilter($filter);
        $criteria->addAssociation('paymentMethod');

        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (null === $transaction) {
            return null;
        }

        return PaymentTransaction::fromOrderTransaction($transaction);
    }

    private function shouldAllowCapture(array $transactionData, PaymentTransaction $paymentTransaction): bool
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

        return $handlerClass::isCapturable($transactionData, $paymentTransaction->getCustomFields());
    }

    private function shouldAllowRefund(array $transactionData, PaymentTransaction $paymentTransaction): bool
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

    private function isTransactionOpen(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_APPOINTED;
    }

    private function isTransactionPaid(array $transactionData): bool
    {
        if (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE && (float) $transactionData['receivable'] !== 0.0) {
            return true;
        }

        return in_array(strtolower($transactionData['txaction']),
            [
                self::ACTION_PAID,
                self::ACTION_COMPLETED,
                self::ACTION_DEBIT,
            ]
        );
    }

    private function isTransactionCancelled(array $transactionData): bool
    {
        return strtolower($transactionData['txaction']) === self::ACTION_CANCELATION
            || strtolower($transactionData['txaction']) === self::ACTION_FAILED
            || (strtolower($transactionData['txaction']) === self::ACTION_CAPTURE && (float) $transactionData['receivable'] === 0.0);
    }

    private function stateExists(string $state, Context $context): bool
    {
        $criteria = new Criteria([$state]);

        return (bool) $this->stateRepository->search($criteria, $context)->first();
    }
}
