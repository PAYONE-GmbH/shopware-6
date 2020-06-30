<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Capture;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
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
    public function capture(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $requestResponse = $this->handleRequest($parameterBag, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float) $parameterBag->get('amount'));
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        $transitionName = StateMachineTransitionActions::ACTION_PAY_PARTIALLY;

        if ($parameterBag->get('complete')) {
            $transitionName = StateMachineTransitionActions::ACTION_PAY;
        }

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            $transitionName
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
