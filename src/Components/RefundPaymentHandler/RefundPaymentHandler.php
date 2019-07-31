<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Throwable;

class RefundPaymentHandler implements RefundPaymentHandlerInterface
{
    /** @var RefundRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        RefundRequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $stateRepository,
        TransactionDataHandlerInterface $dataHandler
    ) {
        $this->requestFactory  = $requestFactory;
        $this->client          = $client;
        $this->stateRepository = $stateRepository;
        $this->dataHandler     = $dataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransaction);

        $request = $this->requestFactory->getRequestParameters($paymentTransaction, $context);

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        } catch (Throwable $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $data = [
            CustomFieldInstaller::TRANSACTION_STATE => 'refunded',
            CustomFieldInstaller::ALLOW_REFUND      => false,
        ];

        $this->dataHandler->logResponse($paymentTransaction, $context, $response);
        $this->dataHandler->incrementSequenceNumber($paymentTransaction, $context);
        $this->dataHandler->setState($paymentTransaction, $context, $this->getRefundedState($context));
        $this->dataHandler->saveTransactionData($paymentTransaction, $context, $data);
    }

    private function getRefundedState(Context $context): StateMachineStateEntity
    {
        $criteria = new Criteria();
        $filter   = new EqualsFilter('state_machine_state.technicalName', OrderTransactionStates::STATE_REFUNDED);
        $criteria->addFilter($filter);

        return $this->stateRepository->search($criteria, $context)->first();
    }
}
