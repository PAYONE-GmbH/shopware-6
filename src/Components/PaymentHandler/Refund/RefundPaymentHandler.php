<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentHandler\Refund;

use PayonePayment\Components\DataHandler\LineItem\LineItemDataHandler;
use PayonePayment\Components\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class RefundPaymentHandler extends AbstractPaymentHandler implements RefundPaymentHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    public function __construct(
        RefundRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        TransactionStatusServiceInterface $transactionStatusService,
        EntityRepositoryInterface $transactionRepository,
        LineItemDataHandler $lineItemDataHandler
    ) {
        $this->requestFactory           = $requestFactory;
        $this->client                   = $client;
        $this->dataHandler              = $dataHandler;
        $this->transactionStatusService = $transactionStatusService;
        $this->transactionRepository    = $transactionRepository;
        $this->lineItemDataHandler      = $lineItemDataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function fullRefund(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $requestResponse = $this->fullRequest($parameterBag, $context);

        if(!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, $this->paymentTransaction->getOrderTransaction()->getAmount()->getTotalPrice());
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []));

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            StateMachineTransitionActions::ACTION_REFUND
        );

        return $requestResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function partialRefund(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $requestResponse = $this->partialRequest($parameterBag, $context);

        if(!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float)$parameterBag->get('amount'));
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []));

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
        StateMachineTransitionActions::ACTION_REFUND_PARTIALLY
        );

        return $requestResponse;
    }

    protected function getAmountCustomField(): string
    {
        return CustomFieldInstaller::REFUNDED_AMOUNT;
    }

    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::REFUNDED_QUANTITY;
    }

    protected function getAllowCustomField(): string
    {
        return CustomFieldInstaller::ALLOW_REFUND;
    }
}
