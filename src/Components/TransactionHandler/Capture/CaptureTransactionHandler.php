<?php

declare(strict_types = 1);

namespace PayonePayment\Components\TransactionHandler\Capture;

use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureTransactionHandler extends AbstractTransactionHandler implements CaptureTransactionHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    public function __construct(
        CaptureRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        TransactionStatusServiceInterface $transactionStatusService,
        EntityRepositoryInterface $transactionRepository,
        EntityRepositoryInterface $lineItemRepository
    ) {
        $this->requestFactory           = $requestFactory;
        $this->client                   = $client;
        $this->dataHandler              = $dataHandler;
        $this->transactionStatusService = $transactionStatusService;
        $this->transactionRepository    = $transactionRepository;
        $this->lineItemRepository       = $lineItemRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function fullCapture(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $requestResponse = $this->fullRequest($parameterBag, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, $this->transaction->getAmount()->getTotalPrice());
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            StateMachineTransitionActions::ACTION_PAY
        );

        return $requestResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function partialCapture(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $requestResponse = $this->partialRequest($parameterBag, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float)$parameterBag->get('amount'));
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            StateMachineTransitionActions::ACTION_PAY_PARTIALLY
        );

        return $requestResponse;
    }

    protected function getAmountCustomField(): string
    {
        return CustomFieldInstaller::CAPTURED_AMOUNT;
    }

    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::CAPTURED_QUANTITY;
    }

    protected function getAllowCustomField(): string
    {
        return CustomFieldInstaller::ALLOW_CAPTURE;
    }
}
