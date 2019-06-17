<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Throwable;

class RefundPaymentHandler implements RefundPaymentHandlerInterface
{
    private const STATE_REFUNDED = 'refunded';

    /** @var RefundRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $repository;

    /** @var EntityRepositoryInterface */
    private $stateRepository;

    public function __construct(
        RefundRequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $repository,
        EntityRepositoryInterface $stateRepository
    ) {
        $this->requestFactory  = $requestFactory;
        $this->client          = $client;
        $this->repository      = $repository;
        $this->stateRepository = $stateRepository;
    }

    /**
     * {@inheritdoc}
     *
     * TODO: Sofort needs additional fields when refunding a transaction. It might be nessessary to have a refund transaction
     * TODO: request per payment method.
     * TODO: Sofort Error: IBAN not valid. Please verify your data.
     */
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromOrderTransaction($orderTransaction);

        $requestBag = new RequestDataBag();

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $requestBag,
            $context
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        } catch (Throwable $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $customFields = $orderTransaction->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;
        $customFields[CustomFieldInstaller::TRANSACTION_STATE]      = 'refunded';

        $data = [
            'id'           => $orderTransaction->getId(),
            'stateId'      => $this->getRefundedState()->getId(),
            'customFields' => $customFields,
        ];

        $this->repository->update([$data], $context);
    }

    private function getRefundedState(): ?StateMachineStateEntity
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $filter   = new EqualsFilter('state_machine_state.technicalName', self::STATE_REFUNDED);
        $criteria->addFilter($filter);

        return $this->stateRepository->search($criteria, $context)->first();
    }
}
